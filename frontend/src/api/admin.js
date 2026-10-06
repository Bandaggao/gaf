import client from './client'

function withoutBlankPassword(data) {
  if (!data?.password) {
    const { password, ...rest } = data
    return rest
  }
  return data
}

export function getDashboard() {
  return client.get('/admin/dashboard')
}

export function updateAdminProfile(data) {
  return client.put('/admin/profile', data)
}

export function getStudents() {
  return client.get('/admin/students')
}

export function createStudent(data) {
  return client.post('/admin/students', data)
}

export function updateStudent(id, data) {
  return client.put(`/admin/students/${id}`, withoutBlankPassword(data))
}

export function deleteStudent(id) {
  return client.delete(`/admin/students/${id}`)
}

export function getTeachers() {
  return client.get('/admin/teachers')
}

export function createTeacher(data) {
  return client.post('/admin/teachers', data)
}

export function updateTeacher(id, data) {
  return client.put(`/admin/teachers/${id}`, withoutBlankPassword(data))
}

export function deleteTeacher(id) {
  return client.delete(`/admin/teachers/${id}`)
}

export function getSections() {
  return client.get('/admin/sections')
}

export function createSection(data) {
  return client.post('/admin/sections', data)
}

export function updateSection(id, data) {
  return client.put(`/admin/sections/${id}`, data)
}

export function deleteSection(id) {
  return client.delete(`/admin/sections/${id}`)
}

export function getSubjects() {
  return client.get('/admin/subjects')
}

export function createSubject(data) {
  return client.post('/admin/subjects', data)
}

export function updateSubject(id, data) {
  return client.put(`/admin/subjects/${id}`, data)
}

export function deleteSubject(id) {
  return client.delete(`/admin/subjects/${id}`)
}

export function getTeachingAssignments() {
  return client.get('/admin/teaching-assignments')
}

export function createTeachingAssignment(data) {
  return client.post('/admin/teaching-assignments', data)
}

export function createTeachingAssignmentsBulk(data) {
  return client.post('/admin/teaching-assignments/bulk', data)
}

export function syncTeacherAssignments(teacherId, data) {
  return client.put(`/admin/teachers/${teacherId}/teaching-assignments`, data)
}

export function updateTeachingAssignment(id, data) {
  return client.put(`/admin/teaching-assignments/${id}`, data)
}

export function deleteTeachingAssignment(id) {
  return client.delete(`/admin/teaching-assignments/${id}`)
}

export function getEnrollmentOptions() {
  return client.get('/admin/enrollment-options')
}

export function getStudentEnrollments(studentId) {
  return client.get(`/admin/students/${studentId}/enrollments`)
}

export function saveStudentEnrollments(studentId, data) {
  return client.post(`/admin/students/${studentId}/enrollments`, data)
}

export function deleteStudentEnrollment(studentId, enrollmentId) {
  return client.delete(`/admin/students/${studentId}/enrollments/${enrollmentId}`)
}

export function bulkEnrollBlock(sectionId) {
  return client.post('/admin/enrollments/bulk', { section_id: sectionId })
}

export function importEnrollments(file) {
  const formData = new FormData()
  formData.append('file', file)
  return client.post('/admin/enrollments/import', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

export function getReports(params) {
  return client.get('/admin/reports', { params })
}

export function downloadReport(params) {
  return client.get('/admin/reports/download', { params, responseType: 'blob' })
}
