# HANDOFF – Nghiên cứu cho dự án test "Quản lý dự án AI" (Doppelherz Việt Nam)

Người nhận: Claude Code (VS Code) và chủ dự án. Ngày lập: 2026-11-19.
Đề bài gốc nằm ở thư mục cha: `../Bai_test_Quan_ly_du_an_AI.docx.md` (hạn nộp 24h kể từ khi nhận đề).

Thư mục này chỉ chứa **kết quả nghiên cứu và dữ liệu khảo sát**. Chưa có dòng code nào của prototype, chưa có báo giá, sơ đồ workflow, bộ prompt hay bảng Câu 1.

## 1. Đọc theo thứ tự
1. `01_phan_tich_de_bai.md` – đề bài kiểm tra gì, hồ sơ doanh nghiệp (CTCP Mastertran), khảo sát website, 7 điểm đau.
2. `02_template_3_kenh_va_huong_prototype.md` – hiện trạng 3 kênh, template nội dung, hướng prototype đề xuất, 11 lưu ý ngành TPBVSK.
3. File này – thông số kỹ thuật để code.

## 2. Trạng thái các hạng mục phải nộp
| Hạng mục đề bài | Trạng thái |
|---|---|
| Câu 1 – bảng tự động hóa 7 phòng ban + 3 ưu tiên | Chưa làm. Có 3 giả thuyết ưu tiên ở mục 5 của file 01 |
| 2.1 Chẩn đoán hiện trạng, câu hỏi khảo sát | Có dữ liệu (file 01 mục 3–4, file 02 mục 1); chưa viết thành bộ câu hỏi |
| 2.2 Kế hoạch triển khai | Chưa làm |
| 2.3 Báo giá 40–50 triệu gồm phí vận hành | Chưa làm |
| 2.4 Sơ đồ workflow | Chưa vẽ; luồng mô tả ở mục 4 dưới đây |
| 2.5 Bộ prompt | Chưa viết; ràng buộc ở mục 5 dưới đây |
| 2.6 Sản phẩm chạy thật | Chưa làm |
| 2.7 Lộ trình mở rộng | Chưa làm |

## 3. Dữ kiện kỹ thuật đã kiểm chứng (đo ngày 2026-11-19)

### Website doppelherz.vn – WordPress 6.7.11, Flatsome, WooCommerce, Rank Math, sau Cloudflare
Các endpoint công khai, chỉ đọc, đã gọi thành công không cần xác thực:
- `GET https://doppelherz.vn/wp-json/wp/v2/posts?per_page=100&page=N&_fields=id,date,modified,link,title,categories,featured_media,author` – header `X-WP-Total` (2125) và `X-WP-TotalPages`.
- `GET .../wp/v2/posts/{id}?_embed=wp:featuredmedia` – `content.rendered`, `excerpt.rendered`; ảnh đại diện ở `_embedded["wp:featuredmedia"][0]` (`source_url`, `media_details.width/height`, `alt_text`).
- `GET .../wp/v2/categories?per_page=100` – 42 danh mục.
- `GET .../wp/v2/product?per_page=100&_fields=id,link,title,content,excerpt` – 36 sản phẩm; `excerpt` chứa "Số XNQC: …/XNQC-ATTP", "Số XNCB: …/ĐKSP" và các dòng công dụng.
- `GET https://doppelherz.vn/feed/` – RSS.
- Khi khảo sát tôi chờ 0,3–0,5 giây giữa các lần gọi. Giữ mức đó; đây là website thật của doanh nghiệp.

Mã danh mục dùng cho bộ lọc:
| id | Tên | Số bài | Gợi ý xử lý |
|---|---|---|---|
| 3 | Kiến thức sức khỏe | 1300 | Loại E – đồng bộ |
| 14 / 797 / 15 / 16 / 794 / 798 | Sức khỏe trẻ em / Vitamin và Khoáng chất / Sức khỏe phụ nữ mang thai / Làm đẹp / Xương khớp / Mãn kinh | 353 / 267 / 202 / 179 / 105 / 42 | Con của id 3 – dùng để chọn board Pinterest |
| 909 / 897 | Sức khỏe phụ nữ / Tăng sức đề kháng | 96 / 56 | Loại E |
| 1 / 19 / 1160 / 1547 | Sự kiện / Trách nhiệm xã hội / Báo chí (2 mục) | 202 / 57 / 114 / 96 | Loại N – chỉ Facebook |
| 787 | Tin nội bộ | 136 | Loại X – chặn |
| 4144 | Chương trình khuyến mại | 126 | Chặn nếu tiêu đề chứa ".pdf" (46 thông báo đại lý ngày 2026-08-25); phần còn lại đưa vào duyệt tay |
| 4079 / 4087 | Health knowledge / Beauty care (tiếng Anh) | 18 / 7 | Bỏ qua ở bản đầu |

### Pinterest Doppelherz Đức (tham chiếu bố cục)
- RSS công khai: `https://de.pinterest.com/doppelherz_de/feed.rss` (19 Pin gần nhất). Ảnh: đổi `/236x/` thành `/736x/` trong URL `i.pinimg.com`.
- Ảnh Pin 736×1104 (2:3). Màu lấy mẫu từ ảnh: đỏ `#AC1C1C`, xanh huy hiệu `#00448F`, chữ trắng.
- Ba bố cục A/B/C mô tả ở file 02 mục 2.3; ảnh mẫu `pinterest_de_12_pin_mau.jpg`.
- Trang chi tiết từng Pin không đọc được bằng công cụ → không có số lượt lưu/nhấp.

### Chưa kiểm chứng – phải xác nhận khi code
- Giới hạn ký tự Pinterest (tiêu đề 100, mô tả 500), kích thước ảnh Facebook, tính năng Pinterest tự tạo Pin từ RSS.
- Facebook: chưa đọc được nội dung trang DoppelherzVietnam.
- Chưa gọi thử Graph API hay Pinterest API. Thông tin về App Review và Trial/Standard access lấy từ tài liệu và bài tổng hợp (xem nguồn ở file 01, 02).

## 4. Kiến trúc prototype đề xuất (chưa được chủ dự án chốt)
```
[WordPress REST] -> [Phân loại E/P/N/X theo danh mục + tiêu đề]
   -> (P) [Tra doppelherz_san_pham_xnqc.csv theo link sản phẩm trong bài]
   -> [Claude: caption Facebook + tiêu đề/mô tả/alt-text Pinterest]
   -> [Kiểm tuân thủ: luật cứng + AI rà]
   -> [Dựng ảnh Pin 1000x1500 theo template A/B/C]
   -> [Hàng chờ duyệt] -> [Xếp lịch] -> [Đăng Facebook / xuất gói Pin] -> [Log + báo cáo]
```
- Nền tảng: hai câu hỏi còn mở với chủ dự án – n8n hay Python cho bản demo; đã có Facebook Page thử nghiệm, tài khoản Pinterest Business, khóa API Claude chưa.
- Đăng Facebook: lên Page thử nghiệm của ứng viên. Pinterest: app mới chỉ có Trial access (Pin chỉ người tạo thấy) nên bản demo xuất gói Pin sẵn sàng đăng và ghi rõ giới hạn.
- Link trong bài social gắn `utm_source=facebook|pinterest` (website hiện 0/40 bài có UTM, 40/40 dùng link rút gọn bom.so).
- Khóa API và token đặt trong biến môi trường, không ghi vào repo.

## 5. Ràng buộc bắt buộc cho prompt và bộ kiểm tuân thủ
Đây là đề xuất kỹ thuật rút từ nguồn thứ cấp, **không thay cho ý kiến pháp chế**. Đặt thành file cấu hình để pháp chế sửa được.
1. Bài loại P chỉ dùng công dụng nguyên văn trong cột `excerpt` của `doppelherz_san_pham_xnqc.csv`; kèm cụm "Thực phẩm bảo vệ sức khỏe", số XNQC và câu "Thực phẩm này không phải là thuốc và không có tác dụng thay thế thuốc chữa bệnh."
2. AI chỉ viết lại từ bài gốc: không thêm số liệu, nghiên cứu, liều dùng, công dụng.
3. Từ chặn (danh sách khởi đầu, cần pháp chế bổ sung): chữa, điều trị, đặc trị, khỏi hẳn, dứt điểm, 100%, cam kết, thần dược, hết bệnh; so sánh nhất (số 1, tốt nhất, duy nhất) chỉ dùng khi có tài liệu của khách hàng.
4. Không hình ảnh hay danh nghĩa bác sĩ, dược sĩ, cơ sở y tế, lời cảm ơn/chứng thực của người bệnh. Prompt sinh ảnh cấm áo blouse, bệnh viện.
5. Câu mở không gán tình trạng sức khỏe cho người đọc. Không ngôn ngữ/hình ảnh giảm cân, không before–after.
6. Sản phẩm cho trẻ em và phụ nữ mang thai: luôn hai lớp duyệt.
7. Mọi bài đều qua người duyệt trước khi đăng; không có chế độ đăng thẳng.

## 6. Danh mục file
| File | Nội dung |
|---|---|
| `01_phan_tich_de_bai.md` | Phân tích đề, doanh nghiệp, khảo sát website, điểm đau |
| `02_template_3_kenh_va_huong_prototype.md` | Hiện trạng 3 kênh, template, hướng prototype, lưu ý ngành |
| `doppelherz_posts_all.csv` | 2.125 bài: id, date, modified, link, title, author, featured_media, category_names, category_ids |
| `doppelherz_posts_12thang.csv` | 219 bài từ 2025-11 đến 2026-10 |
| `doppelherz_danh_muc.csv` | 42 danh mục: id, name, count, parent, slug |
| `doppelherz_san_pham_xnqc.csv` | 36 sản phẩm: product_id, name, link, xnqc, dksp, xnqc_year, excerpt (34/36 khớp định dạng số XNQC) |
| `doppelherz_cau_truc_40_bai.csv` | Số đo cấu trúc 40 bài "Kiến thức sức khỏe" mới nhất |
| `pinterest_de_19_pins.csv` | 19 Pin gần nhất kênh Đức: title, link, pub, desc, img |
| `pinterest_de_boards.csv` | 10 board đọc được và số Pin |
| `pinterest_de_12_pin_mau.jpg` | Ảnh ghép 12 Pin mẫu (ảnh thuộc Doppelherz Đức, chỉ để tham chiếu bố cục) |
| `MANIFEST.csv` | Kích thước và sha256 từng file |

## 7. Giới hạn của nghiên cứu
- Quan sát từ bên ngoài; chưa biết quy trình nội bộ và công cụ doanh nghiệp đang dùng.
- Mẫu cấu trúc bài là 40 bài mới nhất của một danh mục; nhận định bố cục Pin dựa trên 12 ảnh.
- Kênh Pinterest Đức nhỏ (358 người theo dõi) và ngừng đăng từ 07/2025: là mẫu về hình thức, không phải bằng chứng hiệu quả.
- Căn cứ pháp lý quảng cáo TPBVSK đang thay đổi trong năm 2026 (xem file 02 mục 4, điểm 9).
