import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { roleHome } from '@/constants/roles'
import { authToken } from '@/api/authToken'

const routes = [
  {
    path: '/gate',
    name: 'gate-station',
    component: () => import('@/pages/gate/GateStationPage.vue'),
    meta: { public: true },
  },
  {
    path: '/login',
    component: () => import('@/layouts/GuestLayout.vue'),
    meta: { guest: true },
    children: [
      { path: '', name: 'login', component: () => import('@/pages/LoginPage.vue') },
    ],
  },
  {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, role: 'admin' },
    children: [
      { path: '', redirect: '/admin/dashboard' },
      { path: 'dashboard', name: 'admin-dashboard', component: () => import('@/pages/admin/DashboardPage.vue') },
      { path: 'students', name: 'admin-students', component: () => import('@/pages/admin/StudentsPage.vue') },
      { path: 'teachers', name: 'admin-teachers', component: () => import('@/pages/admin/TeachersPage.vue') },
      { path: 'sections', name: 'admin-sections', component: () => import('@/pages/admin/SectionsPage.vue') },
      { path: 'subjects', name: 'admin-subjects', component: () => import('@/pages/admin/SubjectsPage.vue') },
      { path: 'assignments', name: 'admin-assignments', component: () => import('@/pages/admin/AssignmentsPage.vue') },
      { path: 'enrollments', name: 'admin-enrollments', component: () => import('@/pages/admin/EnrollmentsPage.vue') },
      { path: 'reports', name: 'admin-reports', component: () => import('@/pages/admin/ReportsPage.vue') },
    ],
  },
  {
    path: '/teacher',
    component: () => import('@/layouts/TeacherLayout.vue'),
    meta: { requiresAuth: true, role: 'teacher' },
    children: [
      { path: '', redirect: '/teacher/dashboard' },
      { path: 'dashboard', name: 'teacher-dashboard', component: () => import('@/pages/teacher/DashboardPage.vue') },
      { path: 'sessions', name: 'teacher-sessions', component: () => import('@/pages/teacher/SessionsPage.vue') },
      { path: 'sessions/:id', name: 'teacher-session-detail', component: () => import('@/pages/teacher/SessionDetailPage.vue') },
      { path: 'profile', name: 'teacher-profile', component: () => import('@/pages/teacher/ProfilePage.vue') },
    ],
  },
  {
    path: '/student',
    component: () => import('@/layouts/StudentLayout.vue'),
    meta: { requiresAuth: true, role: 'student' },
    children: [
      { path: '', redirect: '/student/dashboard' },
      { path: 'dashboard', name: 'student-dashboard', component: () => import('@/pages/student/DashboardPage.vue') },
      { path: 'scan', name: 'student-scan', component: () => import('@/pages/student/ScanPage.vue') },
      { path: 'profile', name: 'student-profile', component: () => import('@/pages/student/ProfilePage.vue') },
    ],
  },
  {
    path: '/parent',
    component: () => import('@/layouts/ParentLayout.vue'),
    meta: { requiresAuth: true, role: 'parent' },
    children: [
      { path: '', redirect: '/parent/dashboard' },
      { path: 'dashboard', name: 'parent-dashboard', component: () => import('@/pages/parent/DashboardPage.vue') },
    ],
  },
  {
    path: '/',
    redirect: '/login',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

function needsAuth(to) {
  return to.matched.some((record) => record.meta.requiresAuth)
}

function isGuest(to) {
  return to.matched.some((record) => record.meta.guest)
}

function routeRole(to) {
  return to.matched.find((record) => record.meta.role)?.meta.role
}

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (needsAuth(to)) {
    if (!authToken.value) {
      return { name: 'login', query: { redirect: to.fullPath } }
    }

    if (!auth.user) {
      await auth.fetchUser()
    }

    if (!auth.user) {
      return { name: 'login', query: { redirect: to.fullPath } }
    }
  }

  if (isGuest(to) && authToken.value && auth.user) {
    return roleHome[auth.role] ?? '/login'
  }

  const required = routeRole(to)
  if (required && auth.role && auth.role !== required) {
    return roleHome[auth.role] ?? '/login'
  }

  return true
})

export default router
