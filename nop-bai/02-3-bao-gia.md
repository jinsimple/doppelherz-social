# 2.3 Báo giá – Hệ thống đồng bộ nội dung Website → Facebook + Pinterest

**Kính gửi:** Doppelherz Việt Nam (CTCP Mastertran) · **Ngày lập:** 19/11/2026 · **Hiệu lực báo giá:** 30 ngày
**Đi kèm:** 2.1 Chẩn đoán hiện trạng, 2.2 Kế hoạch triển khai

**Nhãn:** [Giả định] = con số chúng tôi chọn để tính, khách xác nhận lại. [Suy luận] = ước tính, chưa có số đo. [Chưa xác minh] = chưa kiểm được trên nguồn chính thức.

---

## 1. Tóm tắt

| | Phương án tối thiểu | **Phương án đề xuất** |
|---|---|---|
| Chi phí một lần (công triển khai) | 30.100.000 | **37.600.000** |
| Chi phí định kỳ 12 tháng – trả thẳng nhà cung cấp (VPS, Claude API) | 6.348.000 | **6.348.000** |
| Chi phí định kỳ – bảo trì của đối tác (9 tháng, sau 3 tháng bảo hành) | 4.500.000 | **4.500.000** |
| Dự phòng vận hành (khách giữ, chỉ chi khi phát sinh) | 1.500.000 | **1.500.000** |
| **Tổng năm đầu** | **42.448.000** | **49.948.000** |
| Trong đó trả cho đối tác | 34.600.000 | 42.100.000 |
| Ngày công đối tác | 16 | 20,5 |
| Thời gian triển khai | 8 tuần | 8 tuần |

Đơn vị: đồng. Cả hai phương án nằm trong khung 40–50 triệu đồng, **đã gồm chi phí vận hành 12 tháng đầu**.

**Hai phương án giống nhau ở phần quan trọng nhất:** lọc nội dung E/P/N/X, tra sổ xác nhận quảng cáo (XNQC), cổng tuân thủ, **người duyệt trước khi đăng**, hai lớp duyệt cho sản phẩm trẻ em và phụ nữ mang thai. Chúng tôi không cắt phần này ở phương án nào. Lý do: mức phạt cho một lỗi quảng cáo được báo chí nêu là 20–40 triệu đồng, ngang ngân sách dự án (xem 2.1, mục A2-3).

**Phương án đề xuất có thêm:** ảnh Pin dọc 2:3 dựng tự động theo 3 mẫu, lịch nội dung trên Google Sheet, báo cáo tuần kèm lượt truy cập theo UTM (nếu website có GA4).

---

## 2. Giả định dùng để tính

| # | Giả định | Giá trị | Căn cứ |
|---|---|---|---|
| G1 | Số bài trên website | Trung bình ~18 bài/tháng; tháng cao nhất 47 bài | 219 bài trong 11/2025–10/2026, đo từ REST API doppelherz.vn ngày 19/11/2026 (`research/01_phan_tich_de_bai.md`) |
| G2 | Số bài tính ngân sách AI | **Trần 60 bài/tháng** | Cao hơn tháng cao nhất (47) để có dư [Giả định] |
| G3 | Chi phí Claude mỗi bài | **Đo trên demo ngày 19/11/2026:** bài ngắn của website demo 1.983 token vào / 1.908 ra ≈ $0,046; bài dài thật của doppelherz.vn 4.685–5.132 vào / 341–1.642 ra ≈ $0,027–0,052 mỗi lần gọi. Bản chính thức thêm lần rà soát và có thể viết lại 1 lần → **trần $0,15/bài** [Suy luận] | Giá Opus 5.5: $4/1 triệu token vào, $20/1 triệu token ra |
| G4 | Ngân sách Claude/tháng | 60 bài × $0,15 = $9 → làm tròn **$10/tháng** | Chi thực tế dự kiến thấp hơn nhiều: 18 bài × $0,07 ≈ $1,3/tháng [Suy luận] |
| G5 | Tỷ giá | **1 USD = 26.500đ** | [Giả định], chưa tra tỷ giá ngày lập báo giá. $10 = 265.000đ |
| G6 | VPS | 2 vCPU / 4 GB RAM / 40 GB SSD: 240.000đ/tháng chưa VAT; cộng VAT 10% = **264.000đ/tháng** | Giá Bizfly Cloud, tra 19/11/2026. Thuế suất 10% [Chưa xác minh] |
| G7 | Make.com cho Pinterest | Gói Free, 1.000 credits/tháng. Mỗi Pin dùng khoảng 3 credits [Suy luận] → 60 Pin ≈ 180 credits | Demo đã chạy được trên gói Free |
| G8 | Đơn giá ngày công (8 giờ) | PM/Tư vấn 2.000.000đ · Kỹ sư tự động hóa 1.800.000đ · Thiết kế 1.200.000đ | [Suy luận] mức tham khảo cho đối tác nhỏ tại Việt Nam, chưa khảo sát thị trường. Là giá trọn gói của đối tác |
| G9 | Hạ tầng | n8n đặt trên VPS trong nước, tài khoản đứng tên khách. Dùng tên miền phụ của doppelherz.vn | Nếu khách có máy chủ nội bộ, bỏ dòng VPS |
| G10 | Kênh | 1 fanpage Facebook, 1 tài khoản Pinterest, 1 website | Theo đề bài |

---

## 3. Bảng chi phí chi tiết – Phương án đề xuất

### 3.1 Chi phí một lần (công triển khai, trả cho đối tác)

| # | Hạng mục | Mô tả | Vai trò | Đơn vị | SL | Đơn giá | Thành tiền |
|---|---|---|---|---|---|---|---|
| 1 | Khảo sát, chẩn đoán | Buổi khảo sát 120 phút + quan sát người đăng bài; biên bản; sơ đồ quy trình hiện tại; bảng phân loại E/P/N/X; báo giá chính thức | PM | ngày công | 2 | 2.000.000 | 4.000.000 |
| 2a | Thiết kế giải pháp | Luồng xử lý, luồng duyệt, bộ luật tuân thủ dạng file cấu hình, prompt bản 1, kế hoạch kiểm thử, bộ 20 bài mẫu | PM | ngày công | 2 | 2.000.000 | 4.000.000 |
| 2b | Thiết kế giải pháp | Thiết kế kỹ thuật: dữ liệu, trạng thái, tích hợp Meta/Make/Pinterest | Kỹ sư | ngày công | 1 | 1.800.000 | 1.800.000 |
| 3 | Hạ tầng | VPS, n8n, HTTPS qua tên miền phụ, sao lưu hằng ngày, phân quyền, kho khóa bí mật | Kỹ sư | ngày công | 1 | 1.800.000 | 1.800.000 |
| 4 | Plugin WordPress | Chuyển plugin demo (đã có lọc E/P/N/X, màn hình duyệt, lịch tự rải, UTM) lên website khách qua bản thử; chỉnh theo quy trình duyệt và danh mục của khách | Kỹ sư | ngày công | 2 | 1.800.000 | 3.600.000 |
| 5 | Luồng n8n | Claude viết nội dung; cổng tuân thủ (luật cứng + rà soát); tra sổ XNQC cho bài sản phẩm; khung giờ đăng rải đều; đăng Facebook; Pinterest qua Make; báo lỗi, nhắc việc duyệt | Kỹ sư | ngày công | 3 | 1.800.000 | 5.400.000 |
| 6a | Mẫu ảnh Pin | 3 bố cục (câu hỏi / dải tiêu đề / sản phẩm), 1000×1500, theo nhận diện thương hiệu | Thiết kế | ngày công | 1,5 | 1.200.000 | 1.800.000 |
| 6b | Dựng ảnh Pin tự động | Ghép tiêu đề tiếng Việt lên mẫu trong n8n | Kỹ sư | ngày công | 1 | 1.800.000 | 1.800.000 |
| 7 | Lịch nội dung và báo cáo tuần | Lịch trên Google Sheet; báo cáo tuần qua email: số bài đăng/lỗi/chờ duyệt theo kênh, lượt truy cập theo UTM nếu có GA4 | Kỹ sư | ngày công | 1 | 1.800.000 | 1.800.000 |
| 8a | Kiểm thử, UAT, chạy song song | Điều phối UAT với 20 bài mẫu; theo dõi 2 tuần chạy song song | PM | ngày công | 1,5 | 2.000.000 | 3.000.000 |
| 8b | Kiểm thử, UAT, chạy song song | Chạy ca lỗi, sửa lỗi, chỉnh prompt | Kỹ sư | ngày công | 1,5 | 1.800.000 | 2.700.000 |
| 9a | Đào tạo, bàn giao | 2 buổi đào tạo (người duyệt; người vận hành), tài liệu vận hành | PM | ngày công | 1 | 2.000.000 | 2.000.000 |
| 9b | Đào tạo, bàn giao | Chuyển tài khoản, mã nguồn, thu hồi quyền đối tác | Kỹ sư | ngày công | 0,5 | 1.800.000 | 900.000 |
| 10 | Quản lý dự án | Họp tuần, báo cáo tuần, theo dõi hồ sơ Meta/Pinterest, xin Pinterest Standard access | PM | ngày công | 1,5 | 2.000.000 | 3.000.000 |
| | **Cộng chi phí một lần** | PM 8 · Kỹ sư 11 · Thiết kế 1,5 = 20,5 ngày công | | | | | **37.600.000** |

Kiểm tra cộng: PM 8 × 2.000.000 = 16.000.000; Kỹ sư 11 × 1.800.000 = 19.800.000; Thiết kế 1,5 × 1.200.000 = 1.800.000. Tổng 37.600.000.

### 3.2 Chi phí định kỳ – 12 tháng đầu

| # | Hạng mục | Mô tả | Loại | Đơn vị | SL | Đơn giá | Thành tiền |
|---|---|---|---|---|---|---|---|
| 11 | VPS chạy n8n | 2 vCPU / 4 GB / 40 GB SSD, trong nước, gồm VAT (G6) | Khách trả thẳng nhà cung cấp | tháng | 12 | 264.000 | 3.168.000 |
| 12 | Claude API | Ngân sách trần $10/tháng (G2–G5). Đặt hạn mức chi tiêu trên tài khoản | Khách trả thẳng Anthropic | tháng | 12 | 265.000 | 3.180.000 |
| 13 | Make.com | Gói Free, cầu nối tạo Pin (G7) | Tài khoản của khách | tháng | 12 | 0 | 0 |
| 14 | n8n | Bản Community tự host | Miễn phí | tháng | 12 | 0 | 0 |
| 15 | Facebook Graph API, Pinterest API | Không thu phí theo lượt [Chưa xác minh trong phiên tra giá; demo dùng không mất phí] | Miễn phí | – | – | 0 | 0 |
| 16 | Tên miền | Dùng tên miền phụ của doppelherz.vn (G9) | Miễn phí | – | – | 0 | 0 |
| 17 | Bảo trì và hỗ trợ | Tháng 4–12 sau nghiệm thu (3 tháng đầu là bảo hành, không tính phí). Nội dung ở mục 7 | Trả cho đối tác | tháng | 9 | 500.000 | 4.500.000 |
| | **Cộng chi phí định kỳ** | | | | | | **10.848.000** |
| 18 | Dự phòng vận hành | Dùng khi: số bài vượt trần, cần nâng Make lên gói trả phí, thuế/phí thanh toán quốc tế cho dịch vụ nước ngoài. Khách giữ, không trả trước cho đối tác | Khách giữ | gói | 1 | 1.500.000 | 1.500.000 |

Kiểm tra cộng: 3.168.000 + 3.180.000 + 4.500.000 = 10.848.000.

### 3.3 Tổng phương án đề xuất

| Khoản | Thành tiền |
|---|---|
| Chi phí một lần | 37.600.000 |
| Chi phí định kỳ 12 tháng | 10.848.000 |
| Dự phòng vận hành | 1.500.000 |
| **Tổng năm đầu** | **49.948.000** |

37.600.000 + 10.848.000 + 1.500.000 = 49.948.000.

---

## 4. Bảng chi phí – Phương án tối thiểu

Bỏ: ảnh Pin dọc theo mẫu (dùng ảnh đại diện như demo), lịch nội dung Google Sheet (dùng khung giờ cố định), phần đọc GA4 trong báo cáo. Giảm công khảo sát, thiết kế, quản lý dự án.

### 4.1 Chi phí một lần

| # | Hạng mục | Vai trò | Đơn vị | SL | Đơn giá | Thành tiền |
|---|---|---|---|---|---|---|
| 1 | Khảo sát, chẩn đoán | PM | ngày công | 1,5 | 2.000.000 | 3.000.000 |
| 2a | Thiết kế giải pháp | PM | ngày công | 1,5 | 2.000.000 | 3.000.000 |
| 2b | Thiết kế giải pháp | Kỹ sư | ngày công | 1 | 1.800.000 | 1.800.000 |
| 3 | Hạ tầng | Kỹ sư | ngày công | 1 | 1.800.000 | 1.800.000 |
| 4 | Plugin WordPress (lọc E/P/N/X, màn hình duyệt, UTM) | Kỹ sư | ngày công | 2 | 1.800.000 | 3.600.000 |
| 5 | Luồng n8n (AI, cổng tuân thủ, tra XNQC, khung giờ cố định, Facebook, Pinterest qua Make, báo lỗi) | Kỹ sư | ngày công | 3 | 1.800.000 | 5.400.000 |
| 6 | Ảnh Pin dọc | – | – | 0 | – | 0 |
| 7 | Báo cáo tuần (chỉ đếm từ nhật ký) | Kỹ sư | ngày công | 0,5 | 1.800.000 | 900.000 |
| 8a | Kiểm thử, UAT, chạy song song 2 tuần | PM | ngày công | 1,5 | 2.000.000 | 3.000.000 |
| 8b | Kiểm thử, UAT, chạy song song 2 tuần | Kỹ sư | ngày công | 1,5 | 1.800.000 | 2.700.000 |
| 9a | Đào tạo, bàn giao | PM | ngày công | 1 | 2.000.000 | 2.000.000 |
| 9b | Đào tạo, bàn giao | Kỹ sư | ngày công | 0,5 | 1.800.000 | 900.000 |
| 10 | Quản lý dự án | PM | ngày công | 1 | 2.000.000 | 2.000.000 |
| | **Cộng** | PM 6,5 · Kỹ sư 9,5 = 16 ngày công | | | | **30.100.000** |

Kiểm tra cộng: 6,5 × 2.000.000 = 13.000.000; 9,5 × 1.800.000 = 17.100.000. Tổng 30.100.000.

### 4.2 Tổng phương án tối thiểu

| Khoản | Thành tiền |
|---|---|
| Chi phí một lần | 30.100.000 |
| Chi phí định kỳ 12 tháng (giống mục 3.2: VPS 3.168.000 + Claude 3.180.000 + bảo trì 4.500.000) | 10.848.000 |
| Dự phòng vận hành | 1.500.000 |
| **Tổng năm đầu** | **42.448.000** |

30.100.000 + 10.848.000 + 1.500.000 = 42.448.000.

Chênh lệch hai phương án: 49.948.000 − 42.448.000 = 7.500.000 (PM 1,5 ngày = 3.000.000; Kỹ sư 1,5 ngày = 2.700.000; Thiết kế 1,5 ngày = 1.800.000).

---

## 5. Phân loại chi phí

| Loại | Gồm các dòng | Tối thiểu | Đề xuất |
|---|---|---|---|
| Một lần, trả cho đối tác | 1–10 | 30.100.000 | 37.600.000 |
| Định kỳ, trả cho đối tác | 17 (bảo trì) | 4.500.000 | 4.500.000 |
| Định kỳ, khách trả thẳng nhà cung cấp (pass-through) | 11 (VPS), 12 (Claude API) | 6.348.000 | 6.348.000 |
| Miễn phí | 13–16 | 0 | 0 |
| Dự phòng, khách giữ | 18 | 1.500.000 | 1.500.000 |

**Nguyên tắc sở hữu:** VPS, Claude API, Make, app Meta, tài khoản Pinterest đều **đứng tên khách**, khách thanh toán trực tiếp. Đối tác không giữ tài khoản, không ăn chênh lệch. Khi kết thúc hợp đồng, hệ thống vẫn chạy mà không cần đối tác.

**Từ năm thứ hai** (theo giá hiện tại): VPS 3.168.000 + Claude tối đa 3.180.000 = 6.348.000đ/năm nếu khách tự vận hành; cộng bảo trì 12 × 500.000 = 6.000.000đ nếu thuê đối tác → 12.348.000đ/năm.

**Cách giảm thêm chi phí vận hành:**
- Nếu khách có máy chủ nội bộ [Chưa xác minh], đặt n8n trên đó → bỏ 3.168.000đ/năm.
- Đổi sang Claude Sonnet 5.5 ($2 / $10 mỗi 1 triệu token, tra 19/11/2026) → chi phí AI giảm một nửa. Chỉ đổi sau khi chạy lại bộ 20 bài mẫu và chất lượng vẫn đạt.

---

## 6. Điều khoản thanh toán

Thanh toán chi phí một lần theo mốc nghiệm thu:

| Đợt | Mốc | Tỷ lệ | Tối thiểu | Đề xuất |
|---|---|---|---|---|
| 1 | Ký hợp đồng (tạm ứng) | 30% | 9.030.000 | 11.280.000 |
| 2 | UAT đạt (cuối tuần 5) | 40% | 12.040.000 | 15.040.000 |
| 3 | Nghiệm thu bàn giao (cuối tuần 8) | 30% | 9.030.000 | 11.280.000 |
| | **Cộng** | 100% | 30.100.000 | 37.600.000 |

- **Bảo trì:** trả theo quý, đầu mỗi quý, 3 kỳ × 1.500.000đ (tháng 4–6, 7–9, 10–12 sau nghiệm thu). Khách được ngừng bảo trì sau mỗi quý.
- **VPS, Claude API:** khách trả thẳng nhà cung cấp hằng tháng.
- Nếu khách chưa có thẻ thanh toán quốc tế cho Claude API: đối tác trả hộ và thu lại đúng hóa đơn của nhà cung cấp, không cộng phí (điều khoản riêng).
- Giá công là giá trọn gói của đối tác. Nếu khách cần hóa đơn VAT cho phần công, hai bên thống nhất lại theo hình thức xuất hóa đơn của đối tác [Chưa xác minh – phụ thuộc pháp nhân đối tác].

---

## 7. Bảo hành và bảo trì

**Bảo hành 3 tháng** kể từ ngày nghiệm thu bàn giao, không tính phí:
- Sửa mọi lỗi làm hệ thống không đạt tiêu chí nghiệm thu (kế hoạch 2.2, mục 3.1–3.2).
- Phản hồi trong 1 ngày làm việc. Lỗi nghiêm trọng (đăng sai, đăng trùng, bài chưa duyệt lên kênh, hệ thống ngừng) khắc phục trong 2 ngày làm việc.
- Nếu Pinterest cấp Standard access trong thời gian bảo hành: chuyển từ Make sang API riêng, không tính phí.
- Không bảo hành: lỗi do khách tự sửa code/cấu hình ngoài tài liệu; lỗi do thay đổi website, theme, plugin khác; thay đổi chính sách hoặc API của Meta, Pinterest, Make, Anthropic (xử lý trong gói bảo trì).

**Bảo trì 500.000đ/tháng** (tháng 4–12):
- Kiểm tra hệ thống mỗi tháng: lượt kẹt, lỗi, sao lưu, dung lượng, chi phí API.
- Cập nhật bản vá n8n và plugin.
- Cấp lại token khi hết hạn; xử lý thay đổi API của nền tảng.
- Cập nhật bộ luật tuân thủ khi người phụ trách pháp lý quảng cáo (khách chỉ định) [Giả định] gửi thay đổi.
- Báo cáo bảo trì hằng tháng.
- Gồm tối đa 2 giờ công/tháng, không cộng dồn. Giờ vượt tính 225.000đ/giờ (= 1.800.000đ ÷ 8), báo trước khi làm.

---

## 8. Không bao gồm

- Viết bài website, sản xuất ảnh/video mới, chụp ảnh sản phẩm.
- Ý kiến pháp lý. Bên phụ trách pháp lý do khách chỉ định chịu trách nhiệm cuối về nội dung đăng. Bộ luật tuân thủ là công cụ hỗ trợ, không thay ý kiến pháp lý.
- Chạy quảng cáo trả phí, quản lý ngân sách quảng cáo.
- Trả lời bình luận và tin nhắn fanpage.
- Kênh khác: Instagram, Zalo OA, YouTube.
- Đồng bộ khi sửa hoặc xóa bài đã đăng (hệ thống chỉ cảnh báo).
- Số liệu tương tác (lượt thích, tiếp cận, lượt lưu Pin).
- Đăng lại kho 2.125 bài cũ lên Pinterest. Thiết kế cho phép làm sau, báo giá riêng.
- Bài tiếng Anh.
- Phí Make gói trả phí, nếu số Pin vượt gói Free (dùng dòng dự phòng 18).
- Chi phí xác minh doanh nghiệp Meta hoặc chuyển quyền fanpage từ agency (nếu có).

---

## 9. Kiểm tra giá công cụ

Tra ngày **19/11/2026**.

| Công cụ | Giá dùng trong báo giá | Nguồn | Trạng thái |
|---|---|---|---|
| n8n Community (tự host) | 0đ | https://docs.n8n.io/hosting/community-edition-features/ – ghi "the free edition" | Đã kiểm. Điều khoản giấy phép (Sustainable Use License) cho phép dùng nội bộ doanh nghiệp: [Chưa xác minh trong phiên tra] |
| n8n Cloud Starter (để so sánh) | 20€/tháng, trả theo năm, 2.500 lượt chạy/tháng | https://n8n.io/pricing | Đã kiểm. 240€/năm, đắt hơn VPS (3.168.000đ/năm). Không chọn |
| Make Free | 0đ, 1.000 credits/tháng | https://www.make.com/en/pricing | Đã kiểm. Demo đã tạo Pin công khai trên gói này |
| Make Core (dự phòng) | Khoảng $9–12/tháng cho 10.000 credits | Các bài tổng hợp bên thứ ba (latenode.com, zapier.com, jetadmin.io, tháng 9/2026) | **[Chưa xác minh]** – trang giá chính thức không hiện số khi tra. Không đưa vào tổng; chỉ chi từ dòng dự phòng |
| VPS Bizfly Cloud gói VPS 4 (2 vCPU / 4 GB / 40 GB SSD) | 240.000đ/tháng chưa VAT | https://bizflycloud.vn/cloud-server/bang-gia – ghi "Bảng giá chưa bao gồm VAT" | Đã kiểm. VAT 10%: **[Chưa xác minh]** |
| VPS khác để đối chiếu | GoDaddy 2 vCPU/4 GB khoảng 473.000đ/tháng (khuyến mại); Viettel IDC 2 vCPU/4 GB khoảng 614.000đ/tháng | Kết quả tìm kiếm | **[Chưa xác minh]** – không mở được trang chính thức |
| Claude Opus 5.5 | $4 / 1 triệu token vào; $20 / 1 triệu token ra | https://platform.claude.com/docs/en/about-claude/pricing | Đã kiểm |
| Claude Sonnet 5.5 (phương án giảm chi phí) | $2 / $10 mỗi 1 triệu token | Như trên | Đã kiểm |
| Facebook Graph API, Pinterest API | 0đ | Tài liệu nền tảng; demo dùng không phát sinh phí | **[Chưa xác minh]** trong phiên tra giá |
| Tỷ giá USD/VND | 26.500đ | – | **[Giả định]** |
| Đơn giá ngày công | 2.000.000 / 1.800.000 / 1.200.000đ | – | **[Suy luận]** |
| Số token mỗi bài | 1.983–5.132 vào, 341–1.908 ra mỗi lần gọi | Nhật ký demo 19/11/2026 (3 bài demo + 3 bài thật của doppelherz.vn) | **Đã đo** trên 6 bài; sẽ đo lại trên bộ 20 bài mẫu |

---

## Nguồn

- Đề bài: `Bai_test_Quan_ly_du_an_AI.docx.md`, Câu 2 mục 3.
- Số bài/tháng của doppelherz.vn (đo 19/11/2026): `research/01_phan_tich_de_bai.md` mục 3.
- Phạm vi, ngày công, mốc: `nop-bai/02-2-ke-hoach-trien-khai.md`. Chẩn đoán: `nop-bai/02-1-chan-doan-hien-trang.md`. Sơ đồ: `nop-bai/04-so-do-quy-trinh.md`.
- Thiết kế và giới hạn của demo: `SocialSync-Brief.md`, `SocialSync-PRD.md`.
- Giá công cụ (tra 19/11/2026): https://n8n.io/pricing ; https://docs.n8n.io/hosting/community-edition-features/ ; https://www.make.com/en/pricing ; https://bizflycloud.vn/cloud-server/bang-gia ; https://platform.claude.com/docs/en/about-claude/pricing
- Giá Make Core tham khảo (thứ cấp): https://latenode.com/blog/make-com-pricing ; https://zapier.com/blog/make-com-pricing/ ; https://www.jetadmin.io/blog/make-pricing/
- Mức phạt quảng cáo TPBVSK (thứ cấp): `research/02_template_3_kenh_va_huong_prototype.md` mục 3–4 và nguồn trong đó.
