const DB_NAME = 'gratclient'
const DB_VERSION = 1

export const STORES = {
  presentation: 'presentation_cache',
  attempts: 'attempt_queue',
  meta: 'queue_meta',
}

let dbPromise = null
let storageProbePromise = null

const openDb = () => {
  if (dbPromise)
  {return dbPromise}

  dbPromise = new Promise((resolve, reject) => {
    if (typeof indexedDB === 'undefined') {
      reject(new Error('indexeddb-unavailable'))
      
      return
    }
    const request = indexedDB.open(DB_NAME, DB_VERSION)

    request.onupgradeneeded = () => {
      const db = request.result
      if (!db.objectStoreNames.contains(STORES.presentation)) {
        db.createObjectStore(STORES.presentation, { keyPath: 'key' })
      }
      if (!db.objectStoreNames.contains(STORES.attempts)) {
        const store = db.createObjectStore(STORES.attempts, { keyPath: 'id' })

        store.createIndex('presentationKey', 'presentationKey', { unique: false })
        store.createIndex('status', 'status', { unique: false })
      }
      if (!db.objectStoreNames.contains(STORES.meta)) {
        db.createObjectStore(STORES.meta, { keyPath: 'key' })
      }
    }
    request.onsuccess = () => resolve(request.result)
    request.onerror = () => reject(request.error || new Error('indexeddb-open-failed'))
  })

  return dbPromise
}

const withStore = async (storeName, mode, runner) => {
  const db = await openDb()
  
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, mode)
    const store = tx.objectStore(storeName)
    const result = runner(store)

    tx.oncomplete = () => resolve(result)
    tx.onerror = () => reject(tx.error || new Error('indexeddb-tx-failed'))
    tx.onabort = () => reject(tx.error || new Error('indexeddb-tx-aborted'))
  })
}

const wrapRequest = request => new Promise((resolve, reject) => {
  request.onsuccess = () => resolve(request.result)
  request.onerror = () => reject(request.error || new Error('indexeddb-request-failed'))
})

export const idbGet = (storeName, key) =>
  withStore(storeName, 'readonly', store => wrapRequest(store.get(key)))

export const idbSet = (storeName, value) =>
  withStore(storeName, 'readwrite', store => wrapRequest(store.put(value)))

export const idbDelete = (storeName, key) =>
  withStore(storeName, 'readwrite', store => wrapRequest(store.delete(key)))

export const idbGetAll = storeName =>
  withStore(storeName, 'readonly', store => wrapRequest(store.getAll()))

export const idbGetAllByIndex = (storeName, indexName, value) =>
  withStore(storeName, 'readonly', store => {
    const index = store.index(indexName)
    
    return wrapRequest(index.getAll(value))
  })

export const isStorageAvailable = async () => {
  if (storageProbePromise)
  {return storageProbePromise}
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search || '')
    if (params.get('noidb') === '1')
    {return false}
  }
  storageProbePromise = (async () => {
    try {
      await idbSet(STORES.meta, { key: 'probe', ts: Date.now() })
      await idbGet(STORES.meta, 'probe')
      await idbDelete(STORES.meta, 'probe')
      
      return true
    } catch {
      return false
    }
  })()
  
  return storageProbePromise
}
