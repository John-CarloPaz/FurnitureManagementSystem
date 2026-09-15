import { useEffect, useRef, useState } from 'react'
import { Upload, Box, Sparkles, Loader2, RotateCw } from 'lucide-react'
import { AxiosError } from 'axios'
import { Button } from '@/components/ui/button'
import { ModelViewer } from '@/components/orders/model-viewer'
import { useCatalogOptions, useGenerateProductModel, useProductVersions, useUploadProductModel } from '@/hooks/use-products'
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
  const { data: options } = useCatalogOptions()
  const versions = useProductVersions(product.id)
  const upload = useUploadProductModel(product.id)
  const generate = useGenerateProductModel(product.id)
  const fileInput = useRef<HTMLInputElement>(null)
  const photoInput = useRef<HTMLInputElement>(null)
  const [url, setUrl] = useState<string>()

  const currentId = product.model?.current_version_id ?? null
  const current = versions.data?.find((v) => v.id === currentId)
  const canManage = has('products.update')
  const canGenerate = canManage && !!options?.model_generation_enabled

  const gen = product.model_generation
  const isGenerating = gen?.status === 'pending' || gen?.status === 'processing'

  useEffect(() => {
    let active = true
    if (currentId) getModelDownloadUrl(currentId).then((u) => active && setUrl(u))
    else setUrl(undefined)
    return () => {
      active = false
    }
  }, [currentId])

  const onModelFile = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) upload.mutate({ file })
    e.target.value = ''
  }

  const onPhotoFile = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) generate.mutate(file)
    e.target.value = ''
  }

  return (
    <div className="space-y-4">
      {url && current ? (
        <ModelViewer url={url} format={current.format} />
      ) : isGenerating ? (
        <div className="flex h-[420px] flex-col items-center justify-center gap-3 rounded-[var(--radius-md)] border border-dashed border-border bg-surface-2 text-muted">
          <Loader2 size={30} className="animate-spin text-walnut" />
          <p className="text-sm text-fg">Generating 3D model from your photo…</p>
          <div className="h-1.5 w-48 overflow-hidden rounded-full bg-border">
            <div className="h-full rounded-full bg-walnut transition-all" style={{ width: `${Math.max(gen?.progress ?? 0, 6)}%` }} />
          </div>
          <p className="text-xs">This can take a couple of minutes. It'll appear here automatically.</p>
        </div>
      ) : (
        <div className="flex h-[420px] flex-col items-center justify-center gap-2 rounded-[var(--radius-md)] border border-dashed border-border bg-surface-2 text-muted">
          <Box size={32} />
          <p className="text-sm">No 3D model yet</p>
        </div>
      )}

      {gen?.status === 'failed' && (
        <p className="text-sm text-[var(--status-danger)]">
          3D generation failed{gen.error ? `: ${gen.error}` : '.'} Try another photo or upload a model manually.
        </p>
      )}

      {canManage && (
        <div className="flex flex-wrap items-center gap-3">
          {canGenerate && (
            <>
              <input ref={photoInput} type="file" accept="image/jpeg,image/png,image/webp" onChange={onPhotoFile} className="hidden" />
              <Button size="sm" onClick={() => photoInput.current?.click()} disabled={generate.isPending || isGenerating}>
                {gen?.status === 'failed' ? <RotateCw size={15} /> : <Sparkles size={15} />}
                {generate.isPending ? 'Starting…' : isGenerating ? 'Generating…' : current ? 'Regenerate from photo' : 'Generate from photo'}
              </Button>
            </>
          )}

          <input ref={fileInput} type="file" accept=".glb,.obj" onChange={onModelFile} className="hidden" />
          <Button variant="secondary" size="sm" onClick={() => fileInput.current?.click()} disabled={upload.isPending}>
            <Upload size={15} />
            {upload.isPending ? 'Uploading…' : current ? 'Upload new version' : 'Upload model'}
          </Button>

          {current && <span className="text-xs text-muted">v{current.version} · {current.format.toUpperCase()}</span>}
        </div>
      )}

      {upload.isError && <p className="text-sm text-[var(--status-danger)]">{uploadErrorMessage(upload.error)}</p>}
    </div>
  )
}
