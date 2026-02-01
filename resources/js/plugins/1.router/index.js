import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { resolveAuthNavigation } from '@/utils/routeGuards'

const routes = [
  {
    path: '/',
    name: 'root',
    component: () => import('@/pages/index.vue'),
    meta: { public: true },
  },
  {
    path: '/second-page',
    name: 'second-page',
    component: () => import('@/pages/index.vue'),
  },
  {
    path: '/assessments',
    name: 'assessments',
    component: () => import('@/pages/assessments/index.vue'),
  },
  {
    // legacy path from gratserver2025
    path: '/pages/list-assessments',
    redirect: { name: 'assessments' },
  },
  {
    path: '/assessments/:id',
    name: 'assessments-id',
    component: () => import('@/pages/assessments/[id].vue'),
    props: true,
  },
  {
    path: '/assessments/:id/password',
    name: 'assessment-password',
    component: () => import('@/pages/assessments/password.vue'),
    props: true,
  },
  {
    path: '/assessments/:id/progress',
    name: 'assessment-progress',
    component: () => import('@/pages/assessments/progress.vue'),
    props: true,
  },
  {
    path: '/assessments/:id/feedback',
    name: 'assessment-feedback',
    component: () => import('@/pages/assessments/feedback.vue'),
    props: true,
  },
  {
    path: '/assessments/:id/scores',
    name: 'assessment-scores',
    component: () => import('@/pages/assessments/scores.vue'),
    props: true,
  },
  {
    path: '/change-password',
    name: 'change-password',
    component: () => import('@/pages/change-password.vue'),
  },
  {
    path: '/users',
    name: 'users',
    component: () => import('@/pages/users/index.vue'),
    meta: { requiresAdmin: true },
  },
  // Student client routes (public)
  {
    path: '/client/:password',
    name: 'client-home',
    // Lazy-load to keep admin bundle lean; students hit /client via the client build.
    component: () => import('@/gratclient/views/Home.vue'),
    meta: { public: true, hideTopNav: true },
    props: true,
  },
  {
    path: '/client',
    name: 'client-incomplete',
    component: () => import('@/gratclient/views/IncompleteLink.vue'),
    meta: { public: true, hideTopNav: true },
  },
  {
    path: '/questions',
    name: 'questions',
    component: () => import('@/pages/questions.vue'),
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/login.vue'),
    meta: { public: true, hideTopNav: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.VITE_BASE || '/'),
  routes,
  scrollBehavior(to) {
    if (to.hash)
      return { el: to.hash, behavior: 'smooth', top: 60 }

    return { top: 0 }
  },
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  await authStore.ensureSession()

  const decision = resolveAuthNavigation(to, authStore)

  if (decision.shouldLogout) {
    await authStore.logout()
    
    return next()
  }

  if (decision.redirect)
    return next(decision.redirect)

  return next()
})

export { router }
export default function (app) {
  app.use(router)
}
