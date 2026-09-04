import { execSync } from 'child_process';
import { readFileSync } from 'fs';
import fs from 'fs/promises';
import path from 'path';

const narrationDir = path.join(__dirname, '../../docs/user-manual/narration');
const audioWorkDir = path.join(__dirname, '../../docs/user-manual/narration/.generated');
const videoDir = path.join(__dirname, '../../docs/user-manual/videos');

type StepSegment = {
  key: string;
  offsetMs: number;
  audioPath: string;
  durationMs: number;
};

type NarrationConfig = {
  title: string;
  voice: string;
  steps: Record<string, string>;
};

let activeSession: NarrationSession | null = null;

export function narrationEnabled() {
  return process.env.NARRATE_WALKTHROUGH === '1';
}

export function startNarration(slug: string) {
  if (!narrationEnabled()) return;
  activeSession = new NarrationSession(slug);
}

export async function narrate(stepKey: string) {
  if (!activeSession) return;
  await activeSession.addStep(stepKey);
}

export async function finalizeNarration(videoSlug: string) {
  if (!activeSession) return null;
  const output = await activeSession.mergeWithVideo(videoSlug);
  activeSession = null;
  return output;
}

class NarrationSession {
  private slug: string;
  private config: NarrationConfig;
  private startTime = Date.now();
  private segments: StepSegment[] = [];
  private sessionDir: string;

  constructor(slug: string) {
    this.slug = slug;
    this.sessionDir = path.join(audioWorkDir, slug);
    const configPath = path.join(narrationDir, `${slug}.json`);
    this.config = JSON.parse(readFileSync(configPath, 'utf8'));
  }

  async addStep(stepKey: string) {
    const text = this.config.steps[stepKey];
    if (!text) {
      console.warn(`[narration] Missing step: ${stepKey} in ${this.slug}.json`);
      return;
    }

    const offsetMs = Date.now() - this.startTime;
    await fs.mkdir(this.sessionDir, { recursive: true });

    const audioPath = path.join(this.sessionDir, `${this.segments.length.toString().padStart(3, '0')}-${stepKey}.mp3`);
    await synthesizeSpeech(text, audioPath, this.config.voice);
    const durationMs = Math.round(parseFloat(getAudioDuration(audioPath)) * 1000);

    this.segments.push({ key: stepKey, offsetMs, audioPath, durationMs });
  }

  async mergeWithVideo(videoSlug: string) {
    if (!this.segments.length) return null;

    const videoPath = path.join(videoDir, `${videoSlug}.webm`);
    const outputPath = path.join(videoDir, `${videoSlug}-narrated.mp4`);

    await fs.mkdir(audioWorkDir, { recursive: true });
    const combinedAudio = path.join(this.sessionDir, 'combined.mp3');
    await buildCombinedAudio(this.segments, combinedAudio);

    const manifestPath = path.join(narrationDir, `${this.slug}-manifest.json`);
    await fs.writeFile(
      manifestPath,
      JSON.stringify({ slug: this.slug, segments: this.segments, output: outputPath }, null, 2),
    );

    mergeVideoAudio(videoPath, combinedAudio, outputPath);

    return outputPath;
  }
}

function synthesizeSpeech(text: string, outputMp3: string, voice: string) {
  const aiff = outputMp3.replace(/\.mp3$/, '.aiff');
  const platform = process.platform;

  if (platform === 'darwin') {
    execSync(`say -v ${voice} -r 170 -o ${shellQuote(aiff)} ${shellQuote(text)}`, { stdio: 'pipe' });
  } else {
    execSync(`espeak-ng -v en -s 150 -w ${shellQuote(aiff)} ${shellQuote(text)}`, { stdio: 'pipe' });
  }

  execSync(
    `ffmpeg -y -loglevel error -i ${shellQuote(aiff)} -ar 44100 -ac 1 ${shellQuote(outputMp3)}`,
    { stdio: 'pipe' },
  );
}

function getAudioDuration(file: string) {
  return execSync(
    `ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ${shellQuote(file)}`,
    { encoding: 'utf8' },
  ).trim();
}

async function buildCombinedAudio(segments: StepSegment[], outputPath: string) {
  if (segments.length === 1) {
    const seg = segments[0];
    if (seg.offsetMs <= 50) {
      execSync(`ffmpeg -y -loglevel error -i ${shellQuote(seg.audioPath)} -c:a libmp3lame ${shellQuote(outputPath)}`, {
        stdio: 'pipe',
      });
      return;
    }
  }

  const inputs = segments.map((s) => `-i ${shellQuote(s.audioPath)}`).join(' ');
  const filters = segments
    .map((seg, i) => `[${i}:a]adelay=${seg.offsetMs}|${seg.offsetMs}[a${i}]`)
    .join(';');
  const mixInputs = segments.map((_, i) => `[a${i}]`).join('');
  const filterComplex = `${filters};${mixInputs}amix=inputs=${segments.length}:duration=longest:dropout_transition=0:normalize=0,alimiter=limit=0.95[aout]`;

  execSync(
    `ffmpeg -y -loglevel error ${inputs} -filter_complex ${shellQuote(filterComplex)} -map "[aout]" ${shellQuote(outputPath)}`,
    { stdio: 'pipe' },
  );
}

function mergeVideoAudio(videoPath: string, audioPath: string, outputPath: string) {
  execSync(
    `ffmpeg -y -loglevel error -i ${shellQuote(videoPath)} -i ${shellQuote(audioPath)} -map 0:v -map 1:a -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -c:a aac -b:a 192k -shortest ${shellQuote(outputPath)}`,
    { stdio: 'pipe' },
  );
}

function shellQuote(value: string) {
  return `'${value.replace(/'/g, `'\\''`)}'`;
}
