#!/usr/bin/env node
/**
 * Re-merge narration audio into walkthrough videos without re-running Playwright.
 * Usage: node scripts/remerge-narration.mjs [admin|teacher|student|parent|all]
 */
import { execSync } from 'child_process';
import { readFileSync } from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const root = path.join(path.dirname(fileURLToPath(import.meta.url)), '..');
const narrationDir = path.join(root, 'docs/user-manual/narration');
const videoDir = path.join(root, 'docs/user-manual/videos');

const roles = {
  admin: 'admin-walkthrough',
  teacher: 'teacher-walkthrough',
  student: 'student-walkthrough',
  parent: 'parent-walkthrough',
};

function shellQuote(value) {
  return `'${value.replace(/'/g, `'\\''`)}'`;
}

function buildCombinedAudio(segments, outputPath) {
  if (segments.length === 1 && segments[0].offsetMs <= 50) {
    execSync(`ffmpeg -y -loglevel error -i ${shellQuote(segments[0].audioPath)} -c:a libmp3lame ${shellQuote(outputPath)}`, {
      stdio: 'inherit',
    });
    return;
  }

  const inputs = segments.map((s) => `-i ${shellQuote(s.audioPath)}`).join(' ');
  const filters = segments.map((seg, i) => `[${i}:a]adelay=${seg.offsetMs}|${seg.offsetMs}[a${i}]`).join(';');
  const mixInputs = segments.map((_, i) => `[a${i}]`).join('');
  const filterComplex = `${filters};${mixInputs}amix=inputs=${segments.length}:duration=longest:dropout_transition=0:normalize=0,alimiter=limit=0.95[aout]`;

  execSync(
    `ffmpeg -y -loglevel error ${inputs} -filter_complex ${shellQuote(filterComplex)} -map "[aout]" ${shellQuote(outputPath)}`,
    { stdio: 'inherit' },
  );
}

function mergeVideoAudio(videoPath, audioPath, outputPath) {
  execSync(
    `ffmpeg -y -loglevel error -i ${shellQuote(videoPath)} -i ${shellQuote(audioPath)} -map 0:v -map 1:a -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -c:a aac -b:a 192k -shortest ${shellQuote(outputPath)}`,
    { stdio: 'inherit' },
  );
}

function remerge(slug) {
  const videoSlug = roles[slug];
  const manifestPath = path.join(narrationDir, `${slug}-manifest.json`);
  const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
  const combinedAudio = path.join(narrationDir, '.generated', slug, 'combined.mp3');
  const videoPath = path.join(videoDir, `${videoSlug}.webm`);
  const outputPath = path.join(videoDir, `${videoSlug}-narrated.mp4`);

  console.log(`Re-merging ${videoSlug}...`);
  buildCombinedAudio(manifest.segments, combinedAudio);
  mergeVideoAudio(videoPath, combinedAudio, outputPath);
  console.log(`Wrote ${outputPath}`);
}

const target = process.argv.slice(2);
const slugs = target.length === 0 || target.includes('all') ? Object.keys(roles) : target;

for (const slug of slugs) {
  if (!roles[slug]) {
    console.error(`Unknown role: ${slug}`);
    process.exit(1);
  }
  const manifestPath = path.join(narrationDir, `${slug}-manifest.json`);
  try {
    readFileSync(manifestPath);
  } catch {
    console.warn(`Skipping ${slug}: missing ${slug}-manifest.json (re-run narrated walkthrough)`);
    continue;
  }
  remerge(slug);
}
