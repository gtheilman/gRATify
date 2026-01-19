import { createRouter, createWebHistory } from 'vue-router'
import { h } from 'vue'
import Home from '../views/Home.vue'
import IncompleteLink from '../views/IncompleteLink.vue'

const NotFound = {
  name: 'NotFound',
  render: () => h('div', 'not found')
}

const routes = [
  {
    path: '/:password',
    name: 'Home',
    component: Home
  },
  {
    path: '/',
    name: 'Incomplete',
    component: IncompleteLink
  },
  {
    path: '/:pathMatch(.*)*',
    component: NotFound,
    name: 'NotFound'
  }
]

const base = import.meta.env.VITE_CLIENT_BASE || '/client'

const router = createRouter({
  history: createWebHistory(base),
  routes
})

export { routes }
export default router
