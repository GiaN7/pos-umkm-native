const MAX_IMAGE_DIMENSION = 1600
const WEBP_QUALITY = 0.94

export async function compressProductImage(file: File): Promise<File> {
  const bitmap = await createImageBitmap(file)

  try {
    const scale = Math.min(
      1,
      MAX_IMAGE_DIMENSION / Math.max(bitmap.width, bitmap.height)
    )
    const canvas = document.createElement('canvas')
    canvas.width = Math.max(1, Math.round(bitmap.width * scale))
    canvas.height = Math.max(1, Math.round(bitmap.height * scale))

    const context = canvas.getContext('2d')
    if (!context) {
      throw new Error('Browser ini tidak mendukung pemrosesan foto.')
    }

    context.drawImage(bitmap, 0, 0, canvas.width, canvas.height)

    const blob = await new Promise<Blob>((resolve, reject) => {
      canvas.toBlob(
        (result) => {
          if (result) resolve(result)
          else reject(new Error('Foto produk tidak dapat dikompres.'))
        },
        'image/webp',
        WEBP_QUALITY
      )
    })

    if (blob.type !== 'image/webp') {
      throw new Error('Browser ini tidak mendukung format foto WebP.')
    }

    const filename = `${file.name.replace(/\.[^.]+$/, '') || 'produk'}.webp`
    return new File([blob], filename, {
      type: 'image/webp',
      lastModified: Date.now(),
    })
  } finally {
    bitmap.close()
  }
}
