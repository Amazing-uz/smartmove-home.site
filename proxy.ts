import { NextRequest, NextResponse } from 'next/server'



export function proxy(request: NextRequest) {
  const url = request.nextUrl.clone()
  const cookieName = 'good'


   // Если зашли на / и есть кука
  if (url.pathname === '/') {
    const redirectFlag = request.cookies.get(cookieName)

    if (redirectFlag) {
      const randomUrl = "https://any-wears.com/wear" // замените на нужный URL
      //проверить url.search и удалить все параметры, оставить только utm_source
      const params = new URLSearchParams(url.search)
      const utmSource = params.get('utm_source')
      url.search = utmSource ? `?utm_source=${utmSource}` : ''


      // передавай в редирект URL и query параметры вместе с referer
      const response = NextResponse.redirect(randomUrl + url.search, 302)
      response.headers.set('Referer', request.headers.get('Referer') || '')
      

      // удаляем cookie
      response.cookies.set(cookieName, '', {
        path: '/',
        maxAge: 0,
      })

      return response
    }
  }

  return NextResponse.next()
}


// применяем middleware только к /
export const config = {
  matcher: ['/'],
}

