#!/bin/bash

# Blacklist Manager - GitHub Upload Script
# Bu script projeyi otomatik olarak GitHub'a yükler

echo "=========================================="
echo "Blacklist Manager - GitHub Upload Tool"
echo "=========================================="
echo ""

# Renk kodları
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Proje dizini
PROJECT_DIR="/var/www/blacklist/blacklist-manager"
cd "$PROJECT_DIR" || exit

# Git kontrolü
if ! command -v git &> /dev/null; then
    echo -e "${RED}✗ Git yüklü değil!${NC}"
    echo "Yüklemek için: sudo apt install git -y"
    exit 1
fi
echo -e "${GREEN}✓ Git yüklü${NC}"

# Git config kontrolü
GIT_USER=$(git config --global user.name)
GIT_EMAIL=$(git config --global user.email)

if [ -z "$GIT_USER" ] || [ -z "$GIT_EMAIL" ]; then
    echo ""
    echo -e "${YELLOW}Git kullanıcı bilgileri ayarlanmamış${NC}"
    read -p "GitHub kullanıcı adın: " username
    read -p "GitHub email adresin: " email

    git config --global user.name "$username"
    git config --global user.email "$email"

    echo -e "${GREEN}✓ Git config ayarlandı${NC}"
fi

echo ""
echo -e "${BLUE}Git User:${NC} $(git config --global user.name)"
echo -e "${BLUE}Git Email:${NC} $(git config --global user.email)"

# GitHub username iste
echo ""
echo -e "${YELLOW}GitHub bilgilerinizi girin:${NC}"
read -p "GitHub kullanıcı adınız (username): " GITHUB_USER

if [ -z "$GITHUB_USER" ]; then
    echo -e "${RED}✗ Kullanıcı adı gerekli!${NC}"
    exit 1
fi

read -p "Repository adı [blacklist-manager]: " REPO_NAME
REPO_NAME=${REPO_NAME:-blacklist-manager}

echo ""
echo -e "${YELLOW}Bağlantı tipi seç:${NC}"
echo "1) SSH (önerilen - daha güvenli)"
echo "2) HTTPS (token gerekir)"
read -p "Seçiminiz [1]: " CONNECTION_TYPE
CONNECTION_TYPE=${CONNECTION_TYPE:-1}

if [ "$CONNECTION_TYPE" == "1" ]; then
    REPO_URL="git@github.com:${GITHUB_USER}/${REPO_NAME}.git"
    echo ""
    echo -e "${BLUE}SSH key kontrolü...${NC}"

    if [ ! -f ~/.ssh/id_ed25519.pub ] && [ ! -f ~/.ssh/id_rsa.pub ]; then
        echo -e "${YELLOW}SSH key bulunamadı. Oluşturuluyor...${NC}"
        ssh-keygen -t ed25519 -C "$GIT_EMAIL" -f ~/.ssh/id_ed25519 -N ""
        echo ""
        echo -e "${GREEN}✓ SSH key oluşturuldu${NC}"
        echo ""
        echo -e "${YELLOW}Public key'iniz (GitHub'a eklemeniz gerekiyor):${NC}"
        echo "================================================"
        cat ~/.ssh/id_ed25519.pub
        echo "================================================"
        echo ""
        echo "1. GitHub → Settings → SSH and GPG keys"
        echo "2. New SSH key"
        echo "3. Yukarıdaki key'i yapıştır"
        echo ""
        read -p "Key'i GitHub'a ekledin mi? (Enter'a bas) " DUMMY
    else
        echo -e "${GREEN}✓ SSH key mevcut${NC}"
    fi
else
    echo ""
    read -sp "GitHub Personal Access Token: " GITHUB_TOKEN
    echo ""
    REPO_URL="https://${GITHUB_TOKEN}@github.com/${GITHUB_USER}/${REPO_NAME}.git"
fi

# Hassas bilgileri temizle
echo ""
echo -e "${BLUE}Hassas bilgiler temizleniyor...${NC}"

# Config dosyasını example'dan kopyala
if [ -f config/config.php ]; then
    mv config/config.php config/config.php.backup
    cp config/config.example.php config/config.php
    echo -e "${GREEN}✓ config.php temizlendi (backup: config.php.backup)${NC}"
fi

# Data dizinini temizle (sadece .gitkeep kalsın)
find data -type f ! -name '.gitkeep' -delete 2>/dev/null
echo -e "${GREEN}✓ data/ dizini temizlendi${NC}"

# Git işlemleri
echo ""
echo -e "${BLUE}Git repository başlatılıyor...${NC}"

# Eğer zaten git repo varsa, temizle
if [ -d .git ]; then
    echo -e "${YELLOW}Mevcut .git dizini bulundu, temizleniyor...${NC}"
    rm -rf .git
fi

git init
echo -e "${GREEN}✓ Git repository başlatıldı${NC}"

echo ""
echo -e "${BLUE}Dosyalar ekleniyor...${NC}"
git add .

echo ""
echo -e "${BLUE}Commit oluşturuluyor...${NC}"
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
- File-based storage (no database required)
- MIT License"

echo -e "${GREEN}✓ Commit oluşturuldu${NC}"

# Branch'i main yap
git branch -M main
echo -e "${GREEN}✓ Branch: main${NC}"

# Remote ekle
echo ""
echo -e "${BLUE}Remote repository ekleniyor...${NC}"
git remote add origin "$REPO_URL"
echo -e "${GREEN}✓ Remote eklendi: $REPO_URL${NC}"

# Push
echo ""
echo -e "${BLUE}GitHub'a yükleniyor...${NC}"
echo -e "${YELLOW}Bu biraz zaman alabilir...${NC}"
echo ""

if git push -u origin main; then
    echo ""
    echo "=========================================="
    echo -e "${GREEN}🎉 BAŞARILI! Proje GitHub'a yüklendi!${NC}"
    echo "=========================================="
    echo ""
    echo -e "${BLUE}Repository URL:${NC}"
    echo "https://github.com/${GITHUB_USER}/${REPO_NAME}"
    echo ""
    echo -e "${YELLOW}Şimdi yapabilecekleriniz:${NC}"
    echo "1. Repository sayfasına git ve kontrol et"
    echo "2. About bölümünü düzenle (açıklama, topics ekle)"
    echo "3. İlk release'i oluştur (v1.0.0)"
    echo "4. README.md'nin düzgün göründüğünü kontrol et"
    echo ""
    echo -e "${GREEN}Topics önerileri:${NC}"
    echo "security, blacklist, whitelist, firewall, php, ip-management, threat-intelligence, cybersecurity"
    echo ""

    # Config'i geri yükle
    if [ -f config/config.php.backup ]; then
        mv config/config.php.backup config/config.php
        echo -e "${GREEN}✓ config.php geri yüklendi${NC}"
    fi

else
    echo ""
    echo -e "${RED}✗ Yükleme başarısız!${NC}"
    echo ""
    echo "Olası sebepler:"
    echo "1. Repository GitHub'da oluşturulmamış"
    echo "2. SSH key GitHub'a eklenmemiş (SSH kullanıyorsan)"
    echo "3. Token geçersiz (HTTPS kullanıyorsan)"
    echo "4. İnternet bağlantısı problemi"
    echo ""
    echo -e "${YELLOW}Çözüm adımları:${NC}"
    echo "1. GitHub'da repository oluştur: https://github.com/new"
    echo "   - Name: $REPO_NAME"
    echo "   - Initialize with README: ❌ HAYIR"
    echo ""
    echo "2. SSH kullanıyorsan, key'i ekle:"
    echo "   GitHub → Settings → SSH keys"
    if [ -f ~/.ssh/id_ed25519.pub ]; then
        echo "   Key:"
        cat ~/.ssh/id_ed25519.pub
    fi
    echo ""
    echo "3. HTTPS kullanıyorsan, token oluştur:"
    echo "   GitHub → Settings → Developer settings → Personal access tokens"
    echo ""
    echo "4. Script'i tekrar çalıştır: bash upload_to_github.sh"

    # Config'i geri yükle
    if [ -f config/config.php.backup ]; then
        mv config/config.php.backup config/config.php
    fi

    exit 1
fi

# Başarılı olunca config'i geri yükle
if [ -f config/config.php.backup ]; then
    echo ""
    read -p "config.php backup'ını geri yüklemek ister misin? [Y/n]: " RESTORE
    RESTORE=${RESTORE:-Y}

    if [[ "$RESTORE" =~ ^[Yy]$ ]]; then
        mv config/config.php.backup config/config.php
        echo -e "${GREEN}✓ config.php geri yüklendi${NC}"
    else
        echo -e "${YELLOW}Backup dosyası: config/config.php.backup${NC}"
    fi
fi

echo ""
echo -e "${GREEN}Hazırsın! İyi kullanımlar! 🚀${NC}"
