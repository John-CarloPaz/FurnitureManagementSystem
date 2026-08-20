import { useEffect, useRef, useState } from 'react'
import { Upload, Box } from 'lucide-react'
import { AxiosError } from 'axios'
import { Button } from '@/components/ui/button'
import { ModelViewer } from '@/components/orders/model-viewer'
import { useProductVersions, useUploadProductModel } from '@/hooks/use-products'
import { getModelDownloadUrl, type Product } from '@/lib/products-api'
import { useAuth } from '@/hooks/use-auth'

function uploadErrorMessage(error: unknown): string {
  const err = error as AxiosError<{ message?: string }>
  // No HTTP response → the request body was rejected before Laravel, usually because
  // the file exceeds PHP's post_max_size / upload_max_filesize.
  if (!err.response) {
    return 'Upload failed — the file likely exceeds the server upload limit (raise PHP upload_max_filesize / post_max_size).'
  }
  if (err.response.status === 413) {
    return 'File too large for the server (raise PHP post_max_size).'
  }
  return err.response.data?.message ?? 'Upload failed. Use a .glb or .obj file under 20 MB.'
}

export function ProductModelPanel({ product }: { product: Product }) {
  const { has } = useAuth()
  const versions = useProductVersions(product.id)
  const upload = useUploadProductModel(product.id)
  const fileInput = useRef<HTMLInputElement>(null)
  const [url, setUrl] = useState<string>()

  const currentId = product.model?.current_version_id ?? null
  const current = versions.data?.find((v) => v.id === currentId)
  const canManage = has('products.manage')

  useEffect(() => {
    let active = true
    if (currentId) getModelDownloadUrl(currentId).then((u) => active && setUrl(u))
    else setUrl(undefined)
    return () => {
      active = false
    }
  }, [currentId])

  const onFile = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) upload.mutate({ file })
    e.target.value = ''
  }

  return (
    <div className="space-y-4">
      {url && current ? (
        <ModelViewer url={url} format={current.format} />
      ) : (
        <div className="flex h-[420px] flex-col items-center justify-center gap-2 rounded-[var(--radius-md)] border border-dashed border-border bg-surface-2 text-muted">
          <Box size={32} />
          <p className="text-sm">No 3D model uploaded yet</p>
        </div>
      )}

      {canManage && (
        <div className="flex flex-wrap items-center gap-3">
          <input ref={fileInput} type="file" accept=".glb,.obj" onChange={onFile} className="hidden" />
          <Button variant="secondary" size="sm" onClick={() => fileInput.current?.click()} disabled={upload.isPending}>
            <Upload size={15} />
            {upload.isPending ? 'Uploading…' : current ? 'Upload new version' : 'Upload model'}
          </Button>
          {current && (
            <span className="text-xs text-muted">v{current.version} · {current.format.toUpperCase()}</span>
          )}
        </div>
      )}
      {upload.isError && (
        <p className="text-sm text-[var(--status-danger)]">{uploadErrorMessage(upload.error)}</p>
      )}
    </div>
  )
}
