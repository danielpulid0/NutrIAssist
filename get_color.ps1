Add-Type -AssemblyName System.Drawing
$img = [System.Drawing.Image]::FromFile('c:\xampp\htdocs\nutriassist\assets\img\icon-192.png')
$bmp = New-Object System.Drawing.Bitmap($img)
Write-Output "Center (96,96):"
Write-Output $bmp.GetPixel(96, 96).Name
Write-Output "Outer (150, 150):"
Write-Output $bmp.GetPixel(150, 150).Name
Write-Output "Background (10, 10):"
Write-Output $bmp.GetPixel(10, 10).Name
