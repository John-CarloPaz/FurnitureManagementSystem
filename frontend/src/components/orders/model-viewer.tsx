import { Component, Suspense, useEffect, useMemo, type ReactNode } from 'react'
import { Canvas, useLoader } from '@react-three/fiber'
import { OrbitControls, Center, Html, ContactShadows, useGLTF } from '@react-three/drei'
import { OBJLoader } from 'three/examples/jsm/loaders/OBJLoader.js'
import * as THREE from 'three'

/** Clone an object graph with independent materials, then tint (or restore) them. */
function useTinted(source: THREE.Object3D, color?: string) {
  const cloned = useMemo(() => {
    const c = source.clone(true)
    c.traverse((o) => {
      const mesh = o as THREE.Mesh
      if (mesh.isMesh && mesh.material) {
        mesh.material = Array.isArray(mesh.material)
          ? mesh.material.map((m) => m.clone())
          : mesh.material.clone()
      }
    })
    return c
  }, [source])

  useEffect(() => {
    cloned.traverse((o) => {
      const mesh = o as THREE.Mesh
      if (!mesh.isMesh || !mesh.material) return
      const mats = Array.isArray(mesh.material) ? mesh.material : [mesh.material]
      mats.forEach((m) => {
        const mat = m as THREE.MeshStandardMaterial
        if (!('color' in mat)) return
        // Stash the model's original colour once so we can restore it on deselect.
        if (mat.userData.__orig === undefined) mat.userData.__orig = mat.color.getHex()
        if (color) mat.color.set(color)
        else mat.color.setHex(mat.userData.__orig as number)
      })
    })
  }, [cloned, color])

  return cloned
}

function GlbModel({ url, color }: { url: string; color?: string }) {
  const { scene } = useGLTF(url)
  return <primitive object={useTinted(scene, color)} />
}

function ObjModel({ url, color }: { url: string; color?: string }) {
  const obj = useLoader(OBJLoader, url)
  return <primitive object={useTinted(obj, color)} />
}

function Loader() {
  return <Html center><span className="text-xs text-fg/50">Loading model…</span></Html>
}

/** Catches loader failures so a broken/missing model shows an error instead of hanging. */
class ModelErrorBoundary extends Component<{ children: ReactNode }, { failed: boolean }> {
  state = { failed: false }

  static getDerivedStateFromError() {
    return { failed: true }
  }

  render() {
    if (this.state.failed) {
      return (
        <Html center>
          <div className="max-w-[220px] text-center text-xs text-fg/70">
            Couldn't load this 3D model. The file may have failed to store — try regenerating or uploading a model.
          </div>
        </Html>
      )
    }
    return this.props.children
  }
}

/** Studio-lit R3F viewer on a bright backdrop so the piece reads clearly — 360° orbit + zoom, live color tint. */
export function ModelViewer({ url, format, color }: { url: string; format: string; color?: string }) {
  return (
    <div className="relative h-[460px] w-full overflow-hidden rounded-[var(--radius-lg)] border border-black/5 bg-gradient-to-b from-white to-[#e9edf2] shadow-[inset_0_1px_30px_rgba(0,0,0,0.04)]">
      <Canvas camera={{ position: [3, 2, 4.5], fov: 42 }} dpr={[1, 2]} gl={{ alpha: true, antialias: true }}>
        <ambientLight intensity={0.85} />
        <hemisphereLight args={['#ffffff', '#d8dde4', 0.6]} />
        <directionalLight position={[5, 8, 5]} intensity={1.15} castShadow />
        <directionalLight position={[-6, 3, -4]} intensity={0.45} />
        <ModelErrorBoundary key={url}>
          <Suspense fallback={<Loader />}>
            <Center>{format === 'obj' ? <ObjModel url={url} color={color} /> : <GlbModel url={url} color={color} />}</Center>
            <ContactShadows position={[0, -1.25, 0]} opacity={0.33} scale={12} blur={2.6} far={5} color="#1c1510" />
          </Suspense>
        </ModelErrorBoundary>
        <OrbitControls makeDefault enablePan enableZoom autoRotate autoRotateSpeed={0.6} />
      </Canvas>
      <div className="pointer-events-none absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full border border-black/5 bg-white/70 px-3 py-1 text-[11px] text-fg/55 backdrop-blur">
        Drag to rotate · scroll to zoom
      </div>
    </div>
  )
}
