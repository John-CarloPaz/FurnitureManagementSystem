import { Suspense } from 'react'
import { Canvas, useLoader } from '@react-three/fiber'
import { OrbitControls, Center, Html } from '@react-three/drei'
import { useGLTF } from '@react-three/drei'
import { OBJLoader } from 'three/examples/jsm/loaders/OBJLoader.js'

function GlbModel({ url }: { url: string }) {
  const { scene } = useGLTF(url)
  return <primitive object={scene} />
}

function ObjModel({ url }: { url: string }) {
  const obj = useLoader(OBJLoader, url)
  return <primitive object={obj} />
}

function Loader() {
  return <Html center><span className="text-xs text-white/70">Loading model…</span></Html>
}

/** Full-bleed R3F viewer — 360° orbit + zoom. Dark canvas even in light mode. */
export function ModelViewer({ url, format }: { url: string; format: string }) {
  return (
    <div className="relative h-[420px] w-full overflow-hidden rounded-[var(--radius-md)] bg-[#1a1613]">
      <Canvas camera={{ position: [3, 2, 4], fov: 45 }} dpr={[1, 2]}>
        <ambientLight intensity={0.7} />
        <directionalLight position={[5, 8, 5]} intensity={1.2} />
        <directionalLight position={[-5, -2, -5]} intensity={0.4} />
        <Suspense fallback={<Loader />}>
          <Center>{format === 'obj' ? <ObjModel url={url} /> : <GlbModel url={url} />}</Center>
        </Suspense>
        <OrbitControls makeDefault enablePan enableZoom />
      </Canvas>
      <div className="pointer-events-none absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-black/40 px-3 py-1 text-[11px] text-white/70 backdrop-blur">
        Drag to rotate · scroll to zoom
      </div>
    </div>
  )
}
