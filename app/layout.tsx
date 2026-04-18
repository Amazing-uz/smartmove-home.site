import React from "react"
import type { Metadata } from 'next'
import { Outfit, Inter } from 'next/font/google'
import { Analytics } from '@vercel/analytics/next'
import './globals.css'

const outfit = Outfit({ subsets: ["latin"], variable: '--font-outfit' });
const inter = Inter({ subsets: ["latin"], variable: '--font-inter' });

export const metadata: Metadata = {
  title: 'SmartMove - The Science of the Home Office',
  description: 'Curating the world\'s best ergonomic setups. Master your space. Perfect your pace.',
  referrer: 'strict-origin',
}

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode
}>) {
  return (
    <html lang="en" className={`${outfit.variable} ${inter.variable}`}>
      <body className={`font-inter antialiased bg-gradient-to-br from-pearl via-pearl to-pearl/95`}>
        {children}
        <Analytics />
      </body>
    </html>
  )
}
