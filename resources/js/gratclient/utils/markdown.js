// Markdown renderer with KaTeX + emoji support for student-facing content.
import MarkdownIt from 'markdown-it'
import asciimathToLatex from 'asciimath-to-latex'
import { full as emojiPlugin } from 'markdown-it-emoji'
import katex from 'katex'
import 'katex/dist/katex.min.css'

const md = new MarkdownIt({
  html: true,
  linkify: true,
  breaks: true,
}).use(emojiPlugin)

const defaultLinkRender = md.renderer.rules.link_open || function (tokens, idx, options, env, self) {
  return self.renderToken(tokens, idx, options)
}

md.renderer.rules.link_open = function (tokens, idx, options, env, self) {
  const token = tokens[idx]

  if (!token.attrGet('target')) {
    token.attrSet('target', '_blank')
  }

  token.attrSet('rel', 'noopener noreferrer')

  return defaultLinkRender(tokens, idx, options, env, self)
}

const renderLatex = (expr, displayMode = false) => {
  try {
    return katex.renderToString(expr, { throwOnError: false, displayMode })
  } catch {
    return expr
  }
}

const renderAscii = (expr, displayMode = false) => {
  try {
    const tex = asciimathToLatex(expr.trim())
    
    return katex.renderToString(tex, { throwOnError: false, displayMode })
  } catch {
    return expr
  }
}

const MATH_PATTERN = /(?:`?\$[\s\S]*?\$`?|`?@[\s\S]*?@`?)/

const renderMathBlocks = text => {
  if (!text) return ''
  const htmlTags = []
  const placeholderRegex = /<[^>]+>/g
  let output = text.replace(placeholderRegex, match => {
    htmlTags.push(match)
    
    return `__HTML_TAG_${htmlTags.length - 1}__`
  })

  // Fast path: if there are no math markers, skip the replace work.
  if (!MATH_PATTERN.test(output)) {
    if (!htmlTags.length) return output
    
    return output.replace(/__HTML_TAG_(\d+)__/g, (_, index) => htmlTags[Number(index)])
  }

  const replacements = [
    { regex: /`\$\$([\s\S]*?)\$\$`/g, fn: expr => renderLatex(expr.trim(), true) },
    { regex: /`\$([\s\S]*?)\$`/g, fn: expr => renderLatex(expr.trim(), false) },
    { regex: /`@([\s\S]*?)@`/g, fn: expr => renderAscii(expr.trim(), true) },
    { regex: /\$\$([\s\S]*?)\$\$/g, fn: expr => renderLatex(expr.trim(), true) },
    { regex: /\$([\s\S]*?)\$/g, fn: expr => renderLatex(expr.trim(), false) },
    { regex: /@([\s\S]*?)@/g, fn: expr => renderAscii(expr.trim(), true) },
  ]

  for (const { regex, fn } of replacements) {
    output = output.replace(regex, (_, expr) => fn(expr))
  }

  if (!htmlTags.length) return output

  return output.replace(/__HTML_TAG_(\d+)__/g, (_, index) => htmlTags[Number(index)])
}

// Simple memoization to avoid re-rendering identical stems/answers repeatedly.
const MARKDOWN_CACHE = new Map()
const MAX_CACHE_ENTRIES = 200

const decodeHtmlEntities = text => {
  if (!text || !text.includes('&')) return text
  
  return text
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, '\'')
    .replace(/&#36;/g, '$')
    .replace(/&#0*36;/gi, '$')
    .replace(/&#x0*24;/gi, '$')
    .replace(/&dollar;/g, '$')
    .replace(/&amp;/g, '&')
}

export function renderMarkdown (content = '') {
  const key = decodeHtmlEntities(content || '')
  const cached = MARKDOWN_CACHE.get(key)
  if (cached) return cached
  const rendered = md.render(renderMathBlocks(key))
  if (MARKDOWN_CACHE.size >= MAX_CACHE_ENTRIES) {
    const firstKey = MARKDOWN_CACHE.keys().next().value
    if (firstKey) MARKDOWN_CACHE.delete(firstKey)
  }
  MARKDOWN_CACHE.set(key, rendered)
  
  return rendered
}

// Legacy async API that simply wraps the sync renderer for compatibility.
export async function renderMarkdownAsync (content = '') {
  return renderMarkdown(content)
}

export function renderMathInDom () {
  // no-op; math is rendered during markdown processing
}
