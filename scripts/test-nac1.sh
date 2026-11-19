#!/bin/sh
# Bo test nac 1: WordPress bao cho n8n khi co bai moi.
# Chay tu thu muc du an (Git Bash):
#   MSYS_NO_PATHCONV=1 docker compose run --rm -T wpcli sh -s < scripts/test-nac1.sh
# Moi lenh wp la mot request rieng => plugin gui sang n8n o cuoi moi lenh.
set -e
TAG="[Test nấc 1 $(date +%H%M%S)]"

echo "== T0. Gui thu (ma dung) =="
wp socialsync test

echo "== Chuan bi anh =="
IMG=$(wp media import /var/www/html/wp-admin/images/wordpress-logo.png --title="Ảnh thử SocialSync" --alt="Logo dùng để thử" --porcelain)
echo "anh #$IMG"

echo "== T1. Bai viet co anh, xuat ban ngay =="
P1=$(wp post create --post_title="$TAG Bài có ảnh" --post_status=publish \
  --post_content='<!-- wp:paragraph --><p>Đoạn 1 có <strong>chữ đậm</strong> &amp; ký tự đặc biệt.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Đoạn 2: bổ sung 1000 mg vitamin C mỗi ngày.</p><!-- /wp:paragraph -->' \
  --meta_input="{\"_thumbnail_id\":$IMG}" --porcelain)
echo "bai #$P1"

echo "== T2. Sua bai da xuat ban (mong doi: khong gui lai) =="
wp post update "$P1" --post_title="$TAG Bài có ảnh (đã sửa)"

echo "== T3. Chuyen ve nhap roi xuat ban lai (mong doi: khong gui lai) =="
wp post update "$P1" --post_status=draft
wp post update "$P1" --post_status=publish

echo "== T4. Bai khong anh =="
P2=$(wp post create --post_title="$TAG Bài không ảnh" --post_content="Nội dung ngắn, không có ảnh." --post_status=publish --porcelain)
echo "bai #$P2"

echo "== T5. Ban nhap (mong doi: khong tao luot) =="
P3=$(wp post create --post_title="$TAG Bản nháp" --post_status=draft --porcelain)
echo "bai #$P3"

echo "== T6. San pham tao qua REST cua WooCommerce (gia + anh duoc luu SAU khi bai chuyen trang thai) =="
PR=$(wp wc product create --user=1 --name="$TAG Sản phẩm" --regular_price=350000 --status=publish \
  --description="Mô tả dài của sản phẩm thử." --short_description="Mô tả ngắn của sản phẩm thử." \
  --images="[{\"id\":$IMG}]" --porcelain)
echo "san pham #$PR"

echo "== Ket qua: cac luot dong bo =="
wp socialsync jobs --limit=10
echo "P1=$P1 P2=$P2 P3=$P3 PR=$PR"
