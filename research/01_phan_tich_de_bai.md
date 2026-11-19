# Phân tích đề test "Quản lý dự án AI" – Doppelherz Việt Nam (CTCP Mastertran)

Ngày khảo sát: 2026-11-19. Số liệu website lấy trực tiếp từ REST API công khai của doppelherz.vn (chỉ đọc).

## 1. Đề bài thực chất đang kiểm tra điều gì

| Phần đề | Năng lực được kiểm tra | Dấu hiệu trong đề |
|---|---|---|
| Câu 1 – bản đồ tự động hóa 7 phòng ban | Hiểu mô hình kinh doanh phân phối dược/TPCN đa kênh; biết xếp ưu tiên theo lợi ích ÷ độ khó | "mức độ ưu tiên", "ước tính lợi ích", "chọn 3 hạng mục làm trước" |
| Câu 2.1 – chẩn đoán | Tư duy tư vấn: hỏi đúng câu trước khi đề xuất | "khám bệnh", "không đóng vai nhân viên làm thuê" |
| Câu 2.2–2.3 – kế hoạch, báo giá | Thực tế về chi phí: 40–50 triệu loại bỏ phương án viết phần mềm riêng, buộc dùng low-code + API và phải tính phí vận hành | "gồm cả chi phí vận hành sau triển khai" |
| Câu 2.4–2.6 – workflow, prompt, sản phẩm chạy thật | Tự tay làm ra được sản phẩm, không chỉ viết slide | "Sản phẩm chạy thật" |
| Câu 2.7 – lộ trình | Tầm nhìn sau dự án đầu | Gợi ý toàn hạng mục vận hành nội bộ: nhân sự, giao ban, ký duyệt, chấm công |

Nhận định: dự án Website→Facebook/Pinterest chỉ là bài mẫu. Phần gợi ý ở 2.7 cho thấy nhu cầu thật của công ty là số hóa vận hành nội bộ; vị trí này là người dẫn dắt chuỗi dự án đó. Bài nộp nên thể hiện dự án mẫu như "dự án số 1 để tạo niềm tin", rồi nối sang lộ trình nội bộ.

## 2. Doanh nghiệp

- Pháp nhân: CTCP Mastertran, nhà phân phối độc quyền Doppelherz (Queisser Pharma, Đức) tại Việt Nam. Mốc thời gian: website công ty ghi thiết lập hợp tác với Queisser Pharma năm 2012 và hai sản phẩm đầu tiên được phân phối từ tháng 6/2013; báo chí (VnExpress, Sức khỏe & Đời sống, Dân trí) ghi "từ năm 2013".
- Kênh: OTC (nhà thuốc), ETC (bệnh viện/phòng khám), MT, Online (website WooCommerce, Shopee, Lazada). Mục tiêu công bố trên trang tuyển dụng/đối tác: ~12.000 đại lý OTC, 5.000 điểm MT.
- Kênh truyền thông đang liên kết từ website: Facebook (DoppelherzVietnam), Instagram, YouTube, Zalo OA. Website **không** có liên kết Pinterest và **không** cài Pinterest tag (có GTM và Facebook Pixel).

## 3. Khảo sát kỹ thuật website (số đo thực tế)

- Nền tảng: WordPress 6.7.11, theme Flatsome, WooCommerce, Rank Math SEO, sau Cloudflare. RSS (`/feed/`) và REST API (`/wp-json/wp/v2/posts`) mở công khai → làm trigger "có bài mới" không cần can thiệp website.
- Quy mô: 2125 bài viết (2020-06-11 → 2026-10-16), 36 sản phẩm, 72 trang, 14.001 media.
- 199 / 2125 bài không có ảnh đại diện → không tạo Pin được nếu không sinh/chọn ảnh.
- Một tài khoản tác giả (id 235) đứng tên 1795 / 2125 bài (84.5%).

Bài đăng theo tháng, 2025-11 → 2026-10 (219 bài; cột phải đã loại 46 bài dạng thông báo PDF):

| Tháng | Tất cả | Bài nội dung |
|---|---|---|
| 2025-11 | 3 | 3 |
| 2025-12 | 27 | 27 |
| 2026-01 | 18 | 18 |
| 2026-02 | 16 | 16 |
| 2026-03 | 30 | 30 |
| 2026-04 | 47 | 47 |
| 2026-05 | 21 | 21 |
| 2026-06 | 2 | 2 |
| 2026-07 | 2 | 2 |
| 2026-08 | 47 | 1 |
| 2026-09 | 5 | 5 |
| 2026-10 | 1 | 1 |

- Chỉ 66 / 342 ngày có bài mới; khoảng trống dài nhất 39 ngày; nhiều ngày đăng 9–10 bài cùng lúc.
- Ngày 2026-08-25 có 46 "bài viết" là file thông báo chương trình bán hàng (ví dụ "2026-008.TB – Chương trình cân bằng kho ETC.pdf") được xuất bản công khai trong danh mục "Chương trình khuyến mại". Theo tiêu đề, đây có vẻ là thông báo dành cho đại lý (suy luận, cần hỏi khách hàng).
- 12 bài thuộc "Tin nội bộ", 17 bài thuộc danh mục tiếng Anh (Health knowledge / Beauty care) trong 12 tháng.

## 4. Điểm đau rút ra (kèm hệ quả cho thiết kế)

1. **Đăng theo đợt, không theo lịch.** Hệ thống cần hàng đợi + lịch đăng rải đều, không phải "có bài là bắn ngay".
2. **Website trộn nội dung người tiêu dùng với thông báo đại lý và tin nội bộ.** Đồng bộ mù sẽ đẩy thông báo chính sách đại lý lên fanpage 628K follower. Bắt buộc có bộ lọc danh mục + bước duyệt.
3. **Ngành có điều kiện về quảng cáo.** Nội dung TPBVSK phải có cụm từ "Thực phẩm bảo vệ sức khỏe" và câu khuyến cáo "Thực phẩm này không phải là thuốc…" (Luật 75/2025/QH15, NĐ 342/2025 hiệu lực 15/02/2026). AI viết lại không được thêm công dụng ngoài bài gốc → prompt phải có ràng buộc tuân thủ + bước kiểm tra tự động + người duyệt.
4. **Pinterest chưa hiện diện trên website** và API có rào cản: app mới ở Trial access thì Pin chỉ người tạo nhìn thấy; muốn công khai phải xin Standard access kèm video demo. Phải đưa vào mốc thời gian và rủi ro.
5. **Kho 2125 bài cũ chưa được tái sử dụng.** Pinterest là kênh tìm kiếm, nội dung "kiến thức sức khỏe" (1.300 bài) là tài sản có thể đăng lại dần → lợi ích lớn nhất của dự án, không chỉ bài mới.
6. **Phụ thuộc một người/một tài khoản đăng bài** → rủi ro vận hành, cũng là lý do tự động hóa.
7. **Facebook:** đăng lên Page của chính doanh nghiệp bằng app do admin Page tạo thì không cần App Review; nếu đối tác dùng app của mình cho Page khách thì cần App Review + xác minh doanh nghiệp (1–3 tuần). Chọn phương án đầu.

## 5. Khuyến nghị cho bài nộp

- **Prototype nên chạy trên dữ liệu thật của doppelherz.vn** (đọc REST API công khai) và đăng lên Page/Board thử nghiệm của ứng viên: WordPress → lọc danh mục → Claude sinh caption Facebook + tiêu đề/mô tả/alt-text Pinterest → kiểm tra tuân thủ → duyệt (Google Sheet/Telegram) → xếp lịch → đăng → ghi log + báo cáo tuần.
- **Công cụ:** n8n tự host (miễn phí bản Community, chỉ trả VPS) hoặc n8n Cloud Starter (~€20–24/tháng); Claude API; Google Sheets làm lịch nội dung + báo cáo. Phù hợp khung 40–50 triệu.
- **Ba hạng mục ưu tiên ở Câu 1 (giả thuyết ban đầu, cần hoàn thiện):** (a) Marketing – dây chuyền nội dung đa kênh có kiểm duyệt tuân thủ; (b) Kinh doanh/Dịch vụ bán hàng – trợ lý tra cứu chương trình bán hàng và sản phẩm cho trình dược viên/đại lý (bằng chứng: 46 thông báo PDF đang phải đưa lên website); (c) CSKH Online – gợi ý trả lời inbox fanpage/Zalo/hotline có rào chắn nội dung y tế.

## 6. Giới hạn của khảo sát này

- Chỉ quan sát từ bên ngoài; quy trình nội bộ, nhân sự marketing, công cụ đang dùng (ERP/DMS/CRM) chưa biết → là nội dung của bộ câu hỏi chẩn đoán.
- Chưa đo tần suất đăng và tương tác trên Facebook; chưa xác minh doanh nghiệp có tài khoản Pinterest hay không (chỉ biết website không liên kết).
- Phần pháp lý là tóm tắt từ nguồn thứ cấp, không thay cho ý kiến pháp chế của doanh nghiệp.

## Nguồn

- doppelherz.vn (REST API, RSS, trang Về chúng tôi, trang Đối tác) – truy cập 2026-11-19
- vietnamworks.com/company/cong-ty-co-phan-mastertran; vnexpress.net (4827093, 4875735); vietnamnet.vn (2381021)
- tuoitre.vn/plo – "Lưu ý về việc quảng cáo mỹ phẩm, thực phẩm từ 15-2-2026"; thuvienphapluat.vn – Luật 75/2025/QH15, NĐ 342/2025/NĐ-CP
- developers.pinterest.com/docs/key-concepts/access-tiers; developers.facebook.com/docs/graph-api/reference/page/feed
- Bảng giá n8n (n8n.io/pricing, qua các bài tổng hợp tháng 10–11/2026)
