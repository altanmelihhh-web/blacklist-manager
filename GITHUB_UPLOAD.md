# 🚀 GitHub'a Yükleme Rehberi

## Adım 1: GitHub'da Yeni Repository Oluştur

1. https://github.com adresine git
2. Sağ üstteki **"+"** butonuna tıkla
3. **"New repository"** seç
4. Repository bilgilerini gir:
   - **Repository name**: `blacklist-manager`
   - **Description**: `Advanced IP Blacklist & Whitelist Management System with Multi-Instance Support`
   - **Public** veya **Private** seç
   - **❌ Initialize with README yok** (bizim zaten var)
   - **❌ .gitignore yok** (bizim zaten var)
   - **✅ Add a license: MIT** (veya boş bırak, bizim zaten var)
5. **"Create repository"** tıkla

## Adım 2: Hassas Bilgileri Temizle

```bash
cd /var/www/blacklist/blacklist-manager

# Config dosyasını kontrol et - özel IP/bilgi var mı?
nano config/config.php

# Varsa, example config'i kullan
cp config/config.example.php config/config.php
```

## Adım 3: Git Kurulumunu Kontrol Et

```bash
# Git yüklü mü?
git --version

# Eğer yüklü değilse:
sudo apt update
sudo apt install git -y

# Git kullanıcı bilgilerini ayarla (ilk kez kullanıyorsan)
git config --global user.name "Your Name"
git config --global user.email "your-email@example.com"
```

## Adım 4: SSH Key veya Token Hazırla

### Seçenek A: SSH Key (Önerilen)

```bash
# SSH key oluştur (eğer yoksa)
ssh-keygen -t ed25519 -C "your-email@example.com"
# Enter'a bas, şifre istersen gir

# Public key'i görüntüle ve kopyala
cat ~/.ssh/id_ed25519.pub

# GitHub'a git:
# Settings → SSH and GPG keys → New SSH key
# Key'i yapıştır ve kaydet
```

### Seçenek B: Personal Access Token

```bash
# GitHub'da:
# Settings → Developer settings → Personal access tokens → Tokens (classic)
# Generate new token → repo seçeneklerini işaretle
# Token'ı kopyala ve sakla (bir daha göremezsin!)
```

## Adım 5: Projeyi GitHub'a Yükle

```bash
cd /var/www/blacklist/blacklist-manager

# Git repository'yi başlat
git init

# Tüm dosyaları stage'e al
git add .

# Hangi dosyalar eklenecek kontrol et
git status

# İlk commit
git commit -m "Initial commit: Blacklist Manager v1.0.0

Features:
- Multi/single instance support with easy mode switching
- Automatic source synchronization from public threat feeds
- Manual IP/FQDN blacklist and whitelist management
- Logo upload and instance customization
- Instance renaming functionality
- CIDR notation support for IP ranges
- Protected IP blocks to prevent infrastructure blocking
- Cron-based automatic updates
- Excel/CSV/JSON import/export
- Modern responsive web interface
- CSRF protection and input validation
- Comprehensive documentation

Tech Stack:
- PHP 7.4+
- Vanilla JS
- No database required (file-based)
- Composer optional"

# Remote repository ekle (SSH ile)
git remote add origin git@github.com:YOURUSERNAME/blacklist-manager.git

# VEYA Token ile:
# git remote add origin https://github.com/YOURUSERNAME/blacklist-manager.git

# Ana branch'i main olarak ayarla
git branch -M main

# GitHub'a push et
git push -u origin main
```

## Adım 6: Token ile Push (Eğer SSH kullanmıyorsan)

```bash
# İlk push'ta kullanıcı adı ve token ister:
Username: your-github-username
Password: your-personal-access-token (token'ı yapıştır)

# Veya doğrudan URL'ye ekleyebilirsin:
git remote set-url origin https://YOUR-TOKEN@github.com/YOURUSERNAME/blacklist-manager.git
git push -u origin main
```

## Adım 7: GitHub'da Kontrol Et

1. https://github.com/YOURUSERNAME/blacklist-manager adresine git
2. README.md'nin düzgün göründüğünü kontrol et
3. Tüm dosyaların yüklendiğini kontrol et

## Adım 8: GitHub Repository'yi Güzelleştir

### Repository Açıklaması Ekle
GitHub'da repository sayfanın üstünde:
- **About** → ⚙️ (ayarlar)
- **Description**: `Advanced IP Blacklist & Whitelist Management System`
- **Website**: (varsa sitenin URL'i)
- **Topics**: `security` `blacklist` `whitelist` `firewall` `php` `ip-management` `threat-intelligence`
- **Save changes**

### README Badge'leri
README.md'nin başına otomatik eklenmiş badge'ler var zaten ✅

## Adım 9: GitHub Releases (Opsiyonel)

```bash
# İlk sürümü release yap
# GitHub'da: Releases → Create a new release
# Tag: v1.0.0
# Title: Blacklist Manager v1.0.0 - Initial Release
# Description: CHANGELOG.md'den kopyala
# Publish release
```

## Hızlı Komutlar (Tek Seferde)

```bash
cd /var/www/blacklist/blacklist-manager

# Git init ve push
git init
git add .
git commit -m "Initial commit: Blacklist Manager v1.0.0"
git branch -M main
git remote add origin git@github.com:YOURUSERNAME/blacklist-manager.git
git push -u origin main
```

## Sorun Giderme

### "remote origin already exists" hatası
```bash
git remote remove origin
git remote add origin git@github.com:YOURUSERNAME/blacklist-manager.git
```

### "Permission denied (publickey)" hatası
```bash
# SSH key'i ekledin mi kontrol et
ssh -T git@github.com

# Eğer hata verirse, SSH key'i yeniden ekle (Adım 4)
```

### "Support for password authentication was removed" hatası
```bash
# Token kullanman gerekiyor, HTTPS URL'ini token ile güncelle:
git remote set-url origin https://YOUR-TOKEN@github.com/YOURUSERNAME/blacklist-manager.git
```

### Büyük dosya hatası (>100MB)
```bash
# Git LFS kur (Large File Storage)
sudo apt install git-lfs
git lfs install
git lfs track "*.zip"
git add .gitattributes
git commit -m "Add LFS tracking"
git push
```

## Gelecekte Güncelleme Yapma

```bash
cd /var/www/blacklist/blacklist-manager

# Değişiklikleri gör
git status

# Değişiklikleri ekle
git add .

# Commit
git commit -m "Add new feature: XYZ"

# Push
git push

# Veya hepsi bir arada:
git add . && git commit -m "Update: description" && git push
```

## GitHub Pages (Opsiyonel)

Eğer statik dokümantasyon sitesi istersen:
1. GitHub repository → Settings → Pages
2. Source: Deploy from a branch
3. Branch: main → /docs
4. Save
5. Site: `https://yourusername.github.io/blacklist-manager`

---

## ✅ Checklist

- [ ] GitHub'da yeni repository oluşturdun
- [ ] Hassas bilgileri temizledin
- [ ] Git kurulumunu yaptın
- [ ] SSH key veya token hazırladın
- [ ] `git init` yaptın
- [ ] `git add .` ile dosyaları ekledin
- [ ] `git commit` ile commit'ledin
- [ ] Remote origin ekledin
- [ ] `git push` ile yükledin
- [ ] GitHub'da kontrol ettin
- [ ] Repository açıklamasını ekledin
- [ ] Topics ekledin
- [ ] (Opsiyonel) Release oluşturdun

## 🎉 Tamamsa, Paylaş!

Repository URL'in:
```
https://github.com/YOURUSERNAME/blacklist-manager
```

İnsanlarla paylaş:
- Twitter
- Reddit (r/PHP, r/netsec)
- LinkedIn
- Dev.to

## 🌟 GitHub Stars İste

README.md'de ekle:
```markdown
If you find this project helpful, please consider giving it a ⭐️ on GitHub!
```
