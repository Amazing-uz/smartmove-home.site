import { Suspense } from 'react'

import { Redirector } from '@/components/redirector'

export default function AmazingPage() {
  return (
    <Suspense fallback={null}>
      <Redirector />
    </Suspense>
  )
}
