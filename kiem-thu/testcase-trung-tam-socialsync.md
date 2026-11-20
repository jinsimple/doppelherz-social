# Bộ testcase SocialSync – bản UX/UI mới (Trung tâm SocialSync)

Lập ngày 20/11/2026, khoảng 09:40. Người lập: Claude, vai tester.
Đối tượng: plugin `social-sync` 0.3.0 cùng luồng n8n, **đọc code lúc 09:30–09:40 khi tab PM vẫn đang sửa**. Nếu code đổi sau giờ đó thì phải đối chiếu lại mục 1 và mục 5.

**Lịch sử thay đổi**

| Bản | Lúc | Thay đổi |
|---|---|---|
| 1.0 | 09:40 | Bản đầu, viết theo code và thiết kế UX |
| 1.1 | ~10:10 | Cập nhật theo trả lời của PM: R1–R6 đều sửa, Q1–Q6 đã chốt, gói 2 đã làm. Thêm nhóm **S (đổi giờ / hủy lịch sau khi duyệt)**. Các kỳ vọng đổi theo PM có ghi *(PM chốt)* |
| 1.2 | ~10:45 | Thêm kết quả chạy vòng 0–5 (mục 6) và bảng tổng kết dưới đây. Vòng kiểm thử khép lại |

## Tổng kết (đọc trước)

**Phạm vi:**
- 192 testcase trong 19 nhóm, gồm cả nhóm S mới (đổi giờ / hủy lịch sau khi duyệt).
- Chạy ngày 20/11/2026 từ 09:50 tới 10:45, trên website demo thật `shop.adaptivealgo.uk` và n8n thật.
- Gần như mọi ca chạy bằng tài khoản người chấm `nguoicham` (Shop manager). Chỉ dùng WP-CLI để tạo bài thử và tạm đổi cấu hình (đã khôi phục).

**Kết quả:**

| Chỉ số | Số |
|---|---|
| Testcase đã chạy và **đạt** | 75, trong đó 4 đạt một phần: ERR-06 (chỉ phần Shop manager), SEC-09 (chỉ phần chưa đăng nhập), CH-05 (chỉ phần Pinterest), E2E-01 (chưa đếm số lần bấm của người thật) |
| Lỗi tìm được và **đã sửa, đã kiểm lại đạt** | 16. Đọc code trước khi chạy: R1–R6. Vòng chỉ đọc: F1–F3. Chạy thật: L1 (P1), L2, L3, L4, L7, L8, L9 |
| Lỗi để vào giới hạn | L6: bấm Đồng bộ lại lần 2 không có thông báo. Hành vi đúng là chỉ gửi n8n 1 lần, và điều này đã đạt |
| Quan sát quy trình | L5: nhãn `[TEST]` trong tiêu đề làm Claude phân loại bài là "Không đồng bộ". Luồng thật đã dùng bài không có nhãn này |
| Câu hỏi thiết kế đã được PM chốt | Q1–Q6, cùng 8 câu của nhóm S (S-07, S-08, S-09, S-11, S-14, EDT-05b, SEC-09, ERR-11d) |
| Lần đăng công khai | 1. Đây là luồng thật đầu-cuối của PM, bài #104. Mọi ca khác dùng "duyệt giờ xa rồi Hủy lịch", và đã chứng minh lệnh cũ không đăng (S-03, S-05) |

**Các điểm quan trọng nhất đã kiểm:**
1. Hủy lịch hoặc đổi giờ sau khi duyệt thì lệnh đăng cũ trong n8n **không đăng**. n8n hỏi lại WordPress và nhận `token_mismatch` hoặc `status_needs_review`.
2. Không đăng trùng: không trùng khi cập nhật bài, khi bài về nháp rồi xuất bản lại, khi gửi lại form duyệt cũ, hay khi bấm Đồng bộ lại 2 lần cùng lúc.
3. Bài bị chặn ở WordPress (danh mục Tin nội bộ) không còn đường nào để lên kênh.
4. Luồng người chấm trên bài thật #104: từ xuất bản tới khi bài lên FB mất 37 giây; FB và Pin công khai, Pin vào đúng board "Làm đẹp". Nhật ký duyệt ghi đúng "Duyệt nguyên văn".

**Để vào giới hạn của sản phẩm** (đã ghi trong `nop-bai/06`):
- **Chưa chạy đầu-cuối trên kênh thật:** CH-04, ERR-02, S-06, E2E-03, vì cần đăng thật hoặc làm hỏng kênh. PM đã kiểm các ca này ở tầng dữ liệu.
- **Chưa kiểm vai Editor và Author:** NAV-03, NAV-04, SEC-05, phần Author của SEC-09.
- **Chưa chạy, mức P1–P2:** S-08, S-09, S-10 (PM đã thử ở tầng dữ liệu), SEC-04, AI-03, AI-06, AI-07, RPT-04, RPT-05, CFG-01–04, UI-03–06, các ca EDT còn lại.
- **Dữ liệu còn để nguyên theo quyết định của PM:** ký tự CR trong nội dung đã duyệt cũ của các lượt #69, #73, #83 (đã từ chối, không đăng). Tiền tố thừa trong lý do của L3.

**Dữ liệu thử còn trên website:** 10 bài `[TEST]` từ #92 tới #101, mọi lượt đã từ chối hoặc bỏ qua; #101 để Nháp. Có xoá trước khi nộp hay không là việc chủ dự án quyết. Bài mẫu #82 và #86 vẫn ở *Cần duyệt*, chưa ai đụng.

> **Cổng chạy:** chưa chạy testcase nào làm đổi dữ liệu thật (đăng, duyệt, từ chối, đồng bộ lại) cho tới khi PM nhắn **"SẴN SÀNG CHẠY"** kèm danh sách thay đổi cuối cùng. Trước tin đó chỉ được kiểm những gì chỉ đọc (menu, quyền, hiển thị).

---

## 0. Cách đọc

**Nhãn ở cột "Kết quả mong đợi":**

| Nhãn | Nghĩa |
|---|---|
| *(không nhãn)* | Lấy từ code hiện tại hoặc từ PRD |
| **[UX]** | Theo `de-xuat-ux-socialsync.md`. Code có thể chưa làm hết |
| **[Suy luận]** | Tester suy ra từ code, chưa chạy thử |
| **[Cần PM chốt]** | Code đang làm một kiểu, có thể chưa đúng ý thiết kế. PM quyết rồi mới chấm Đạt/Lỗi |

**Mức ưu tiên:**
- **P0:** luồng người chấm chắc chắn đi qua. Phải đạt hết trước khi nộp.
- **P1:** luồng phụ hoặc luồng lỗi quan trọng.
- **P2:** nên kiểm nếu còn giờ.

**Cột KQ:** ghi `Đạt`, `Lỗi`, `Chặn` (không chạy được vì phụ thuộc) hoặc `Bỏ`. Khi ghi Lỗi, kèm ảnh màn hình và dòng log (`logs/`, hoặc lệnh `wp socialsync log`).

---

## 1. Điều kiện vào kiểm thử (tình trạng lúc lập)

| # | Điều kiện | Lúc 09:40 | Ghi chú |
|---|---|---|---|
| E1 | `class-socialsync-hub.php` được nạp, `SocialSync_Hub::init()` được gọi | Có | `social-sync.php:42`, `:74` |
| E2 | Menu SocialSync mở Trung tâm | Có | `class-socialsync-admin.php:24-25` |
| E3 | Có `assets/hub.js` và `assets/hub.css` | Có | Mới tạo trong buổi sáng |
| E4 | Các hàm Trung tâm gọi sang Nhật ký đều `public` (`warning`, `render_actions`, `render_notice`, `local`) | Có | `class-socialsync-log-page.php` |
| E5 | `php -l` chạy sạch mọi file PHP. Bật `WP_DEBUG`, không có notice nào | **Chưa chạy** | Chạy trước khi test (REG-03) |
| E6 | Đã commit hoặc tạo nhánh sao lưu trước khi sửa | **Chưa xác minh** | Đang có 7 file sửa, 1 file mới chưa commit |
| E7 | n8n đã nạp luồng mới: bỏ qua Pin khi bài không có ảnh, sửa chữ R6, **hỏi lại WordPress trước khi đăng** (nhóm S) | **Chưa xác minh** | `socialsync-main.json` đang có thay đổi chưa commit. Phải nạp lại bằng `scripts/n8n-nap-luong.ps1` |
| E8 | PM đã nhắn "SẴN SÀNG CHẠY" | **Chưa** | PM sẽ gửi kèm danh sách thay đổi cuối cùng |
| E9 | Không còn lượt nào đang chờ trong n8n từ luồng **cũ** (luồng chưa hỏi lại WordPress) | **Chưa xác minh** | Xem S-12. Nếu còn, lượt đó có thể đăng dù đã bấm Hủy lịch |

Chỉ bắt đầu vòng P0 khi E1–E5, E7, E8 đã đạt.

---

## 2. Chuẩn bị

### 2.1 Tài khoản

| Tài khoản | Vai trò | Dùng để |
|---|---|---|
| admin | Administrator | Cấu hình, đăng lại bài đã đăng, làm hỏng có chủ đích |
| `nguoicham` | Shop manager | **Luồng người chấm**. Phần lớn testcase P0 chạy bằng tài khoản này |
| test-editor *(tạo mới)* | Editor | Kiểm quyền |
| test-author *(tạo mới)* | Author | Kiểm quyền: phải không thấy SocialSync |

### 2.2 Quy tắc an toàn (đọc trước khi bấm Duyệt)

1. **Không đụng bài mẫu #82 và #86.** Hai bài này phải còn ở *Cần duyệt* cho người chấm (REG-01).
2. **Mọi lượt đã duyệt đều sẽ đăng thật lên Fanpage và Pinterest công khai**, trừ khi Hủy lịch có tác dụng thật.
   - **Trước khi S-05 và S-06 đạt:** coi như không hủy được. Giới hạn tổng số lần duyệt thật khoảng 5–6. Các ca còn lại dùng *Từ chối* hoặc bỏ chọn kênh.
   - **Sau khi S-05 và S-06 đạt:** theo đề xuất của PM, các ca duyệt dùng "Duyệt theo giờ" đặt xa (ví dụ 21:30 tối nay) rồi *Hủy lịch*. Chỉ giữ 2–3 lần đăng thật cho REV-01, CH-01, CH-03, E2E-01, E2E-03.
   - Sau mỗi ca có Hủy lịch, kiểm Fanpage và Pinterest sau giờ cũ khoảng 2 phút để chắc chắn không có bài lọt lên.
   - Test xong thì xoá bài thử trên Facebook và Pinterest.
3. Mọi lần đổi Cấu hình để làm hỏng (URL sai, tắt kênh, Tự đăng, khung giờ hẹp…) phải **khôi phục ngay** sau ca test. Mặc định demo là **Duyệt bắt buộc**.

### 2.3 Bộ bài thử

| Mã | Bài | Dùng cho |
|---|---|---|
| D1 | Bài kiến thức, có ảnh đại diện (ví dụ Omega-3) | Luồng chính, phân loại Kiến thức |
| D2 | Bài kiến thức **không có ảnh** | Pinterest bị bỏ qua vì thiếu ảnh |
| D3 | Sản phẩm WooCommerce có ảnh và giá | Phân loại Sản phẩm, giá, câu khuyến cáo |
| D4 | Tin sự kiện hoặc hoạt động xã hội của công ty | Tin thương hiệu, chỉ đăng Facebook |
| D5 | "Thông báo chương trình chiết khấu cho nhà thuốc, đại lý" | AI phân loại Không đồng bộ |
| D6 | Bài thuộc danh mục **Tin nội bộ** | Chặn ngay ở WordPress |
| D7 | Bài có tiêu đề `thong-bao-dai-ly.pdf` | Chặn vì tiêu đề là tên file PDF |
| D8 | Bài có câu "chữa khỏi 100%", "cam kết" | Cảnh báo cụm từ rủi ro |
| D9 | Bài về phụ nữ mang thai hoặc trẻ em | Cờ nhóm nhạy cảm |
| D10 | Tiêu đề có `<script>alert(1)</script>`, `&`, emoji | Kiểm XSS và cách hiển thị |

### 2.4 Công cụ

- Hai trình duyệt, hoặc một cửa sổ thường và một cửa sổ ẩn danh, đăng nhập hai tài khoản khác nhau.
- DevTools: sửa `maxlength`, nhập giá trị bậy, tắt JavaScript, giả lập màn hình 375px.
- WP-CLI trong container: `wp socialsync jobs`, `wp socialsync log`, `wp socialsync reset <post_id>`.
- Danh sách lần chạy trên n8n. File log trong `logs/`.
- Công cụ E2E của PM (Edge headless): `C:\Users\SOIDOC~1\AppData\Local\Temp\claude\043d4591ddd6ebb9\27bda797-e47a-4120-88f4-e3b93b462fcf\scratchpad\shot\cdp.py`. Chạy bằng `python -I cdp.py <.env> <thư mục ra> <kịch bản.json>`; kịch bản mẫu là `t_e2e.json` cùng thư mục. Script tự đọc GRADER_PASSWORD, không in ra. Thư mục scratchpad có thể bị dọn khi khởi động lại máy.
- PM đã tự chạy một lượt E2E bằng `nguoicham`: bài #90 (đã từ chối, dữ liệu thử) đi qua EDT-02, EDT-05, REV-07, REV-17. Tester vẫn chạy lại sau "SẴN SÀNG CHẠY".

---

## 3. Testcase

### A. Menu, điều hướng, phân quyền (NAV)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| NAV-01 | Menu chính mở Trung tâm | Admin bấm menu **SocialSync** | Mở trang "Trung tâm SocialSync". Menu con theo thứ tự: Trung tâm, Báo cáo, Nhật ký chi tiết, Chạy thử doppelherz.vn, Cấu hình. Số đỏ trên menu = số **bài** (không phải số lượt) đang chờ duyệt | P0 | |
| NAV-02 | Shop manager không vào được Cấu hình | `nguoicham`: xem menu, rồi mở thẳng `admin.php?page=social-sync` | Không có mục Cấu hình. Mở thẳng link thì WordPress báo không đủ quyền. Thanh đầu Trung tâm không có link "Cấu hình" | P0 | |
| NAV-03 | Editor dùng được Trung tâm | test-editor mở SocialSync | Thấy menu giống Shop manager. Duyệt được | P1 | |
| NAV-04 | Author không thấy SocialSync | test-author: xem menu, mở thẳng `?page=socialsync`, mở danh sách Bài viết | Không có menu. Mở thẳng thì bị chặn. Danh sách bài **không có** cột "Mạng xã hội" | P1 | |
| NAV-05 | Link màn Chờ duyệt cũ vẫn mở được | Mở `admin.php?page=socialsync-review`, rồi `...&post_id=<D1>`. Làm bằng `nguoicham`, rồi bằng test-author | `nguoicham`: lần 1 vào Trung tâm, tab *Cần duyệt*; lần 2 vào Trung tâm mở sẵn chi tiết D1 (trước bản sửa, link này trả 403). test-author: được chuyển hướng rồi bị chặn ở Trung tâm, không thấy nội dung *(PM: chuyển hướng chạy trước bước kiểm quyền)* | P0 | |
| NAV-06 | Link Nhật ký cũ đổi nghĩa | Mở `?page=socialsync` (trước đây là Nhật ký), rồi `?page=socialsync-log` | Link thứ nhất giờ mở **Trung tâm**. Link thứ hai mở Nhật ký chi tiết. Rà mọi link trong tài liệu 04, 06 và trong các thông báo xem có trỏ đúng không | P1 | |
| NAV-07 | Link trên thanh đầu Trung tâm | Bấm lần lượt Báo cáo, Nhật ký chi tiết, Viết bài mới, Cấu hình (chỉ admin thấy) | Mỗi link mở đúng trang | P1 | |
| NAV-08 | Số chờ duyệt cập nhật | Ghi lại số trên menu → duyệt 1 bài → tải lại | Số giảm đúng 1. Số này bằng số trên tab *Cần duyệt* và bằng "(còn N)" cộng 1 ở ô "mở bài tiếp theo" | P1 | |
| NAV-09 | Cảnh báo thiếu cấu hình | Admin xoá địa chỉ webhook rồi lưu. Xem bằng admin và bằng `nguoicham` | Chỉ admin thấy thông báo vàng "SocialSync chưa đồng bộ được…" kèm link Mở cấu hình. **Khôi phục cấu hình ngay** | P2 | |

### B. Trình soạn bài (EDT)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| EDT-01 | Bảng lịch đăng trong trình soạn khối | Bài viết → Viết bài mới → cột phải | Có bảng "Lịch đăng mạng xã hội" với 2 ô ngày giờ (Facebook, Pinterest). Kênh đang tắt có chữ "(kênh đang tắt)". Ở chế độ duyệt có dòng "Nội dung AI viết sẽ chờ duyệt…" | P1 | |
| EDT-02 | Chọn giờ tay | Ô Facebook chọn giờ hiện tại + 2 tiếng → Đăng | Trong Trung tâm, ô giờ của thẻ Facebook đúng giờ đã chọn (không bị bộ tự rải đổi). Pinterest để trống thì được tự rải | P1 | |
| EDT-03 | Giờ tay đã qua | Chọn giờ của 1 tiếng trước → Đăng | Giờ đề xuất là "bây giờ". Log `schedule.planned` ghi "đã qua nên đăng ngay" | P2 | |
| EDT-04 | Để trống: hệ thống tự rải | Để trống cả 2 ô → Đăng | Giờ đề xuất nằm trong khung 07:00–22:00 và cách bài khác cùng kênh ít nhất 30 phút | P1 | |
| EDT-05 | Bảng tự hiện trạng thái sau khi bấm Đăng | Viết bài mới → bấm Đăng → ở lại trình soạn, không tải lại trang | *(PM chốt: gói 2 đã làm)* Bảng tự hỏi lại mỗi 10 giây (`admin-ajax`, action `socialsync_post_status`). Trong khoảng 10–30 giây tự hiện trạng thái từng kênh (FB trước, Pin sau), ví dụ "Chờ duyệt", và nút "Mở trong Trung tâm". Không còn câu "đổi giờ trong SocialSync → Chờ duyệt". PM đo được 24 giây | P0 | |
| EDT-05b | Bảng ngừng hỏi khi trạng thái đã ổn định | Sau EDT-05, mở DevTools → Network, chờ 2 phút | Bảng ngừng gửi yêu cầu khi các kênh đã ở trạng thái chờ người (Chờ duyệt) hoặc đã kết thúc. **[Cần PM chốt]** điều kiện dừng, để tránh mỗi tab trình soạn gọi máy chủ mãi | P2 | |
| EDT-05c | Mở lại bài đã đồng bộ | Mở bài đã đăng hoặc đã từ chối | Bảng hiện trạng thái hiện tại (giờ đăng, link bài trên kênh nếu có). Bấm "Mở trong Trung tâm" thì mở đúng bài | P1 | |
| EDT-05d | Hộp trạng thái của sản phẩm | Mở sản phẩm D3 đã đồng bộ | Hộp meta hiện trạng thái từng kênh, giống EDT-05c | P1 | |
| EDT-06 | Hộp lịch đăng của sản phẩm | Sản phẩm → Thêm mới | Hộp "Lịch đăng mạng xã hội" ở cột phải. Nhập giờ, lưu nháp, mở lại thì giờ vẫn còn. Đăng thì giờ được dùng | P1 | |
| EDT-07 | Giờ nhập sai định dạng | Dùng DevTools đổi giá trị ô thành `abc` → Đăng | Giá trị bị làm rỗng, hệ thống tự rải. Không lỗi PHP | P2 | |
| EDT-08 | Đăng bài không bị chậm | Bấm Đăng 3 lần trên 3 bài khác nhau, bấm giờ | Nút Đăng xong trong khoảng thời gian bình thường của WordPress, chậm thêm không quá 1 giây (PRD mục 10). Chế độ gửi là "Chạy nền" | P2 | |

### C. Phát hiện xuất bản và chống đăng trùng (PUB)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| PUB-01 | Xuất bản bài mới | Đăng D1 → mở Trung tâm | Trong khoảng 10–30 giây, bài hiện ở tab *AI đang viết*, rồi chuyển sang *Cần duyệt*. Có 1 dòng với 2 chip FB và Pin | P0 | |
| PUB-02 | Cập nhật bài đã xuất bản | Sửa D1 → Cập nhật | Không có lượt mới. Log `hook.skip` ghi "bài đã xuất bản từ trước" | P0 | |
| PUB-03 | Về nháp rồi xuất bản lại | Đổi D1 về Nháp → Đăng lại | Không có lượt mới. Log ghi "mọi kênh đã có lượt đồng bộ từ trước" | P0 | |
| PUB-04 | Nháp, riêng tư, có mật khẩu | Lưu nháp. Đăng ở chế độ Riêng tư. Đăng có mật khẩu | Cả 3 trường hợp đều không tạo lượt. Log ghi đúng lý do | P1 | |
| PUB-05 | Bài hẹn giờ của WordPress | Hẹn xuất bản sau 5 phút | Lượt chỉ được tạo khi bài thật sự chuyển sang Đã xuất bản. Cần WP-Cron chạy **[Chưa xác minh]** | P1 | |
| PUB-06 | Bấm Đăng nhiều lần | Mở bài mới ở 2 tab, bấm Đăng gần như cùng lúc | Mỗi kênh chỉ có 1 lượt, nhờ ràng buộc duy nhất `post_channel` | P1 | |
| PUB-07 | Chặn theo danh mục | Đăng D6 (Tin nội bộ) | 2 lượt "Không đăng", lý do *thuộc danh mục bị chặn "Tin nội bộ"*. Không gọi AI. Bài nằm ở tab *Bỏ qua / Từ chối*. Chi tiết ghi phân loại "Không đồng bộ" | P1 | |
| PUB-08 | Chặn theo tiêu đề PDF | Đăng D7 | Như PUB-07, lý do "tiêu đề là tên file PDF" | P2 | |
| PUB-09 | Tắt kênh hoặc loại nội dung | Admin tắt Pinterest → đăng bài. Bỏ loại Sản phẩm → đăng sản phẩm. **Khôi phục sau đó** | Bài chỉ có lượt Facebook. Sản phẩm không có lượt nào. Log ghi lý do | P2 | |
| PUB-10 | Nhiều bài cùng lúc | Đăng 3 bài liên tiếp trong 1 phút | Giờ đề xuất cùng kênh cách nhau ít nhất 30 phút, không bị trùng giờ (có khoá xếp lịch) | P1 | |

### D. AI phân loại và viết nội dung (AI)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| AI-01 | Bài kiến thức | Đăng D1 → xem chi tiết | Phân loại *Kiến thức* kèm lý do. Nguồn *Claude viết*, có model, token vào/ra, số giây. Nội dung Facebook và Pinterest khác nhau, đều có link về bài gốc | P0 | |
| AI-02 | Sản phẩm có giá | Đăng D3 | Phân loại *Sản phẩm*. Facebook có dòng "Giá: …" và câu khuyến cáo "Thực phẩm này không phải là thuốc…" nằm ở dòng riêng. Mô tả Pin kết thúc bằng câu khuyến cáo và không quá 500 ký tự | P0 | |
| AI-03 | Tin thương hiệu | Đăng D4 | Facebook chờ duyệt. Pinterest "Không đăng: Tin thương hiệu chỉ đăng Facebook". Thẻ Pin không dùng màu cảnh báo **[UX 3.5]** | P1 | |
| AI-04 | AI phân loại Không đồng bộ | Đăng D5 | Cả 2 kênh "Không đăng", lý do do AI đưa ra. Bài nằm ở tab *Bỏ qua / Từ chối* | P1 | |
| AI-05 | Bài không có ảnh | Đăng D2 | Ngay khi AI viết xong, Pinterest đã ở trạng thái "Không đăng: thiếu ảnh", Facebook ở *Chờ duyệt*. Chip ở danh sách ghi "Pin — Không đăng: thiếu ảnh". Chi tiết ghi "Ảnh đại diện: Không có" kèm link thêm ảnh | P1 | |
| AI-06 | Cụm từ rủi ro | Đăng D8 | Có 2 khả năng. (a) AI tránh được cụm từ. (b) Nội dung mẫu cố định dựng lại cụm từ từ bài gốc: danh sách hiện cờ "Có từ rủi ro" và "Nội dung mẫu (AI lỗi)", thẻ kênh hiện ⚠. Ghi lại xảy ra khả năng nào | P1 | |
| AI-07 | Nhóm nhạy cảm | Đăng D9 | Cờ "⚠ Nhóm nhạy cảm" ở dòng danh sách. Hộp đỏ ở chi tiết | P1 | |
| AI-08 | AI lỗi thì dùng mẫu | Admin làm sai khoá Claude, chạy lại script nạp luồng → đăng 1 bài → **khôi phục khoá** | Bài vẫn vào *Chờ duyệt* bằng nội dung mẫu. Nguồn "Mẫu cố định" kèm lý do (HTTP 401). Danh sách có cờ "Nội dung mẫu (AI lỗi)". Duyệt và đăng vẫn được | P1 | |
| AI-09 | Bài rất ngắn | Bài chỉ có 1 câu | Không bao giờ ra nội dung rỗng. Nếu AI không đạt kiểm tra thì dùng mẫu và ghi lý do | P2 | |
| AI-10 | AI không thêm con số | Bài có 2 con số → so với nội dung AI | Bằng mắt: không có con số nào ngoài bài gốc. **[Cần PM chốt]** PRD US-008 có luật kiểm con số, nhưng code n8n hiện chỉ kiểm độ dài và từ cấm | P2 | |

### E. Trung tâm: thanh đầu, tab, ô tìm, danh sách (HUBL)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| HUBL-01 | Thanh đầu cho biết chế độ | `nguoicham` mở Trung tâm | Thấy "Chế độ: Duyệt bắt buộc", khung giờ, số bài tối đa mỗi ngày mỗi kênh, khoảng cách tối thiểu | P0 | |
| HUBL-02 | Chế độ Tự đăng | Admin đổi sang Tự đăng → xem Trung tâm, lần 1 khi không còn bài chờ duyệt, lần 2 khi còn bài cũ chờ duyệt (ví dụ #82). **Đổi lại ngay** | Thanh đầu ghi "Chế độ: Tự đăng". *(PM chốt)* Không còn bài chờ duyệt thì **ẩn** tab *Cần duyệt*. Còn bài cũ chờ duyệt thì tab vẫn hiện | P2 | |
| HUBL-03 | Tab mở mặc định | Mở Trung tâm khi có bài chờ duyệt, rồi khi không còn bài nào | Có bài chờ duyệt thì mở tab *Cần duyệt*. Không có thì mở *Tất cả* | P0 | |
| HUBL-04 | Mỗi dòng là một bài | Xem bài D1 | 1 dòng duy nhất: tên bài, "Bài viết #id · phân loại", 2 chip FB và Pin, mỗi chip có trạng thái và giờ | P0 | |
| HUBL-05 | Số trên tab đúng | Đếm thủ công rồi so với `wp socialsync jobs` | Mỗi tab đếm số **bài**. Một bài có thể nằm ở nhiều tab (ví dụ FB Đã đăng và Pin Lỗi). Tab *Tất cả* = tổng số bài có lượt | P1 | |
| HUBL-06 | Tab AI đang viết | Xem ngay sau khi đăng bài, rồi khi không còn bài nào đang viết | Tab chỉ hiện khi có bài đang ở trạng thái chờ xử lý hoặc đang tạo | P2 | |
| HUBL-07 | Tab Lỗi gồm cả lượt treo | Tạo một lượt treo (xem ERR-08) | Bài nằm ở tab *Lỗi*. Chip đỏ có ⚠. Tab *Lỗi* có màu nhấn khi số lớn hơn 0 | P1 | |
| HUBL-08 | Tab Bỏ qua / Từ chối | So bài D5 (cả 2 kênh bỏ qua) với bài D2 (FB chờ duyệt, Pin bỏ qua) | D5 có ở tab. D2 **không** có, vì còn một kênh đang chờ duyệt | P1 | |
| HUBL-09 | Thứ tự sắp xếp | Có 3 bài chờ duyệt | Tab *Cần duyệt*: bài cũ nhất ở trên. Các tab khác: bài mới cập nhật ở trên | P1 | |
| HUBL-10 | Tìm theo tên | Gõ "omega", "OMEGA", "suc khoe" (không dấu), "sức khoẻ" (khác kiểu bỏ dấu với "sức khỏe"), "Đ" và "d" | Không phân biệt chữ hoa, chữ thường. *(PM chốt)* Gõ không dấu vẫn khớp, vì bỏ dấu cả hai vế bằng `remove_accents()`. Ghi lại kết quả của "khoẻ"/"khỏe" và "đ"/"d" | P1 | |
| HUBL-11 | Tìm theo mã bài | Gõ `#86`, rồi `86` | Ra đúng bài #86 (cùng với các bài có "86" trong tên) | P2 | |
| HUBL-12 | Tìm không có kết quả | Gõ "zzzz" | Danh sách báo "Không có bài nào khớp “zzzz”". Cột chi tiết hiện trạng thái trống, không lỗi | P1 | |
| HUBL-13 | Giữ ngữ cảnh khi chuyển | Tìm "vitamin" → đổi tab → chọn 1 bài | Ô tìm giữ nguyên khi đổi tab. Chọn bài thì vẫn giữ tab, từ khoá và trang hiện tại. Trang cuộn tới phần chi tiết | P1 | |
| HUBL-14 | Phân trang | Có hơn 25 bài (dùng dữ liệu thử) | 25 bài mỗi trang. Chuyển trang vẫn giữ tab và từ khoá | P2 | |
| HUBL-15 | Bài đã xoá hẳn | Xoá hẳn một bài thử | Không lỗi trắng trang. Bài hiện "(bài #N … đã xoá)", không có link Sửa, nút thao tác ghi "Bài gốc đã bị xoá" | P1 | |
| HUBL-15b | Bài không còn ở trạng thái Đã xuất bản | Một bài thử đang **chờ duyệt**: lần lượt cho vào thùng rác, đổi về nháp, đổi sang riêng tư. Mỗi lần mở Trung tâm. Thử thêm với một lượt **lỗi** | *(PM chốt)* Có băng "Bài đang ở <trạng thái>, không đăng được". Không có nút Duyệt hay Đồng bộ lại. Gửi POST giả (DevTools) cũng bị máy chủ chặn. Khôi phục bài về Đã xuất bản thì duyệt lại được | P1 | |
| HUBL-16 | Ký tự đặc biệt | Đăng D10 | Tiêu đề hiện đúng chữ, không chạy hộp `alert`. `&` không bị thành `&amp;amp;` | P1 | |
| HUBL-17 | Nhiều dữ liệu | Có trên 1.000 bài | Trang tải chấp nhận được. *(PM chốt: chấp nhận cho demo)* Code chỉ đọc 2.000 lượt mới nhất, bài cũ hơn không hiện trong Trung tâm. Kiểm tài liệu lộ trình có ghi "phân trang ở SQL" | P2 | |

### F. Trung tâm: chi tiết bài (HUBD)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| HUBD-01 | Phần đầu chi tiết | Chọn D1 | Có tên bài, "Bài viết #id", link Sửa bài, link Xem trên website (mở tab mới). Các ô Phân loại + lý do, Nguồn nội dung, Ảnh đại diện có hình nhỏ | P0 | |
| HUBD-02 | Gửi link cho đồng nghiệp | Copy URL có `post_id` → mở bằng tài khoản khác | Mở đúng bài đó | P1 | |
| HUBD-03 | Bài không có ảnh | Chọn D2 | Ô ảnh ghi "Không có" màu đỏ, kèm giải thích và link "Thêm ảnh đại diện ↗" mở trình soạn ở tab mới | P1 | |
| HUBD-04 | Bài chưa có lượt nào | Mở `?page=socialsync&post_id=<bài cũ chưa đồng bộ>` | Báo "Bài này chưa có lượt đồng bộ nào…". Không lỗi | P2 | |
| HUBD-05 | Vừa có form duyệt vừa có kênh khác | Chọn D2 | Phần "Chờ duyệt" có thẻ Facebook. Bên dưới có "Kênh khác của bài": thẻ Pinterest "Không đăng: thiếu ảnh" | P0 | |
| HUBD-06 | Thẻ kết quả khi đã đăng | Chọn bài đã đăng | Chip "Đã đăng HH:MM dd/mm". Nút "Mở bài trên Facebook ↗" mở đúng bài. Dòng Người duyệt có tên, giờ, ghi chú ("Duyệt nguyên văn" hoặc "Người duyệt sửa: …"). Có link lượt chạy n8n | P0 | |
| HUBD-07 | Thẻ "Đã lên lịch" | Duyệt theo giờ đặt xa → xem thẻ | *(PM: đang làm)* Thẻ có ô giờ cùng 2 nút "Đổi giờ" và "Hủy lịch, trả về Chờ duyệt". Không còn dòng "🔒… chưa có ở bản demo". Chi tiết ở nhóm S | P1 | |
| HUBD-07b | Thẻ kênh "Không đăng" | Xem thẻ Pin của D2 (bỏ qua vì thiếu ảnh) | *(PM)* Không hiện giờ đăng hay người duyệt cũ. Có hướng dẫn thêm ảnh | P2 | |
| HUBD-08 | Lượt lỗi | Chọn bài có lượt lỗi | Lỗi hiện **đầy đủ**, không cắt. Chip đỏ. Có nút đồng bộ lại (xem nhóm ERR) | P1 | |
| HUBD-09 | Lượt bị từ chối | Chọn bài đã từ chối | Chip "⊘ Đã từ chối" màu tím xám, không phải màu đỏ. Hiện lý do | P1 | |
| HUBD-10 | Tương tác Facebook | Chạy báo cáo (RPT-04) → xem bài đã đăng | Dòng "x cảm xúc · y bình luận · z chia sẻ (đo …)" | P2 | |
| HUBD-11 | Dòng thời gian | Mở "Dòng thời gian" của bài đã đăng | Các sự kiện theo đúng thứ tự: xuất bản (tên tác giả), tạo lượt, duyệt bởi…, đã đăng. Giờ theo múi giờ website | P1 | |
| HUBD-12 | Nội dung đã đăng khớp thực tế | Mở "Nội dung đã đăng" → so với bài trên Facebook và Pin | Trùng khớp, kể cả phần người duyệt đã sửa | P1 | |

### G. Duyệt, sửa, từ chối (REV)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| REV-01 | Duyệt và đăng ngay *(đăng thật)* | `nguoicham`, bài D1 → bấm "Duyệt và đăng ngay" | Thông báo xanh nêu từng kênh và giờ. Trang ở lại Trung tâm. Nếu đang tick "mở bài tiếp theo" và còn bài khác thì mở bài đó. Bài trên Facebook lên trong khoảng 7–60 giây, có link. Pin lên được | P0 | |
| REV-02 | Duyệt theo giờ *(đăng thật)* | Đặt giờ = bây giờ + 10 phút → "Duyệt và đăng theo giờ đã chọn" | Chip "Đã lên lịch HH:MM". Bài lên đúng giờ, lệch không quá 1–2 phút | P0 | |
| REV-03 | Sửa nội dung Facebook rồi duyệt | Thêm một câu vào ô Facebook → duyệt | Bài đăng đúng bản đã sửa. Ghi chú duyệt "Người duyệt sửa: message" | P0 | |
| REV-04 | Sửa tiêu đề Pin | Sửa tiêu đề Pin → duyệt | Pin đăng với tiêu đề mới | P1 | |
| REV-05 | Bỏ một kênh | Bỏ tick Pinterest → duyệt | Pin chuyển "Đã từ chối", ghi "Người duyệt bỏ kênh này". Thẻ Pin mờ đi ngay khi bỏ tick. Facebook được lên lịch | P1 | |
| REV-06 | Bỏ cả hai kênh | Bỏ tick cả 2 → bấm Duyệt | Báo lỗi "Chưa chọn kênh nào để đăng. Muốn bỏ cả bài thì dùng Từ chối." Các lượt vẫn ở *Chờ duyệt* | P1 | |
| REV-07 | Từ chối có lý do | Bấm một lý do nhanh → "Từ chối toàn bộ" → OK | Ô lý do được điền sẵn. Có hộp xác nhận. Mọi lượt chờ duyệt chuyển "Đã từ chối" kèm lý do. Bài rời tab *Cần duyệt*. Không đăng gì | P0 | |
| REV-08 | Từ chối khi chưa có lý do | Để trống ô lý do. Sau đó tắt JavaScript rồi thử lại | Nút "Từ chối toàn bộ" bị mờ cho tới khi có lý do. Khi tắt JS thì máy chủ báo "Cần ghi lý do khi từ chối." và các lượt vẫn chờ duyệt | P1 | |
| REV-09 | Huỷ hộp xác nhận | Bấm Từ chối → chọn Huỷ | Không có gì thay đổi | P2 | |
| REV-10 | Hai người duyệt cùng một bài | Admin và `nguoicham` cùng mở 1 bài. Người A duyệt trước, người B duyệt sau | Người B nhận thông báo "Bài không còn lượt nào chờ duyệt: <tên A> đã duyệt lúc HH:MM dd/mm". Không đăng lần hai | P0 | |
| REV-11 | Một người duyệt, một người từ chối cùng lúc | Như REV-10, người B bấm Từ chối | Chỉ quyết định đến trước có hiệu lực. Người B được báo ai đã xử lý | P1 | |
| REV-12 | Kiểm tra độ dài trên trình duyệt | Sửa vài chỗ, rồi xoá trống ô Facebook (hoặc tiêu đề Pin) → bấm Duyệt | *(PM chốt)* Form **không gửi đi**. Báo lỗi ngay tại ô sai. Các phần đã sửa vẫn còn nguyên | P1 | |
| REV-12b | Kiểm tra độ dài ở máy chủ, giữ bản sửa | Tắt JS (hoặc gỡ `maxlength` bằng DevTools). Sửa vài ô, để Facebook trên 5.000 ký tự hoặc mô tả Pin trên 500 → Duyệt | Máy chủ báo lỗi đúng nội dung, lượt vẫn chờ duyệt. *(PM chốt)* Trang mở lại với **bản đang sửa được điền lại** (lưu tạm theo người dùng và bài). Người khác mở bài này **không** thấy bản nháp đó. Duyệt thành công xong thì bản lưu tạm bị xoá | P1 | |
| REV-13 | Giờ đăng không hợp lệ | DevTools gán ô giờ = `abc` → Duyệt theo giờ | Báo "Giờ đăng không hợp lệ: abc" | P2 | |
| REV-14 | Giờ đã qua | Đặt giờ của hôm qua → Duyệt theo giờ | Đăng ngay (code lấy giờ lớn hơn giữa giờ đã chọn và hiện tại) | P1 | |
| REV-15 | Để trống ô giờ | Xoá trống ô giờ của Facebook → "Duyệt và đăng theo giờ đã chọn". Sau đó bấm "Duyệt và đăng ngay" với ô vẫn trống | *(PM chốt)* Lần 1 báo lỗi "Chọn giờ đăng cho Facebook Fanpage, hoặc bấm Duyệt và đăng ngay". **Không kênh nào được duyệt**, kể cả Pin có giờ hợp lệ. Lần 2 thì đăng ngay bình thường | P1 | |
| REV-15b | Giờ đề xuất đã qua | Mở một bài chờ duyệt lâu, có giờ đề xuất đã qua | *(PM)* Thẻ báo giờ đề xuất đã qua, kèm hậu quả nếu duyệt theo giờ đó (đăng ngay) | P2 | |
| REV-16 | Gỡ ảnh sau khi AI đã viết | Bài có ảnh đang chờ duyệt → gỡ ảnh đại diện trong trình soạn → quay lại Trung tâm | Thẻ Pin bị bỏ tick sẵn, có cảnh báo đỏ. Tick lại rồi duyệt thì bị chặn: "Bài không có ảnh đại diện nên không đăng được Pin…". Facebook **không** bị duyệt theo, vì máy chủ kiểm tra hết rồi mới đổi trạng thái | P1 | |
| REV-17 | Cảnh báo cụm từ rủi ro | Mở bài D8, hoặc gõ "100%" vào ô Facebook | Hiện "⚠ Cụm từ quảng cáo sức khỏe rủi ro: …" ngay khi gõ. Xoá cụm từ thì cảnh báo ẩn. Vẫn cho duyệt (chỉ cảnh báo) | P1 | |
| REV-18 | Câu khuyến cáo không bị coi là từ cấm | Bài sản phẩm D3: câu khuyến cáo có "thay thế thuốc chữa bệnh" | **Không** cảnh báo vì câu khuyến cáo. Tự gõ thêm "chữa bệnh" ở chỗ khác thì có cảnh báo | P1 | |
| REV-19 | Đếm ký tự | Gõ vào từng ô | Bộ đếm "n / max ký tự" cập nhật khi gõ. Vượt giới hạn thì bộ đếm đổi kiểu `is-over`. Emoji tính là 1 ký tự | P2 | |
| REV-20 | Không gửi được lệnh đăng sang n8n | Admin đổi ô "Lệnh đăng sau khi duyệt" thành URL sai → `nguoicham` duyệt → **khôi phục URL** → duyệt lại | Báo "Không gửi được lệnh đăng sang n8n, lượt đã trả về Chờ duyệt: …". Lượt vẫn ở tab *Cần duyệt*. Sau khi khôi phục thì duyệt thành công | P1 | |
| REV-21 | Bỏ tick "mở bài tiếp theo" | Bỏ tick → duyệt | Ở lại bài vừa duyệt, thấy thẻ kết quả | P1 | |
| REV-21b | Xem lại bài vừa duyệt | Để tick → duyệt bài A → trang mở bài B → bấm link trong thông báo | *(PM chốt)* Thông báo có link "Xem lại bài vừa duyệt", mở đúng bài A kèm thẻ kết quả. Cũng làm với Từ chối | P1 | |
| REV-22 | Duyệt bài cuối cùng | Chỉ còn 1 bài chờ duyệt, có tick → duyệt | Thông báo có "Đã hết bài chờ duyệt.". Ở lại bài vừa xử lý | P1 | |
| REV-23 | Thứ tự "bài tiếp theo" | Có 3 bài chờ duyệt A, B, C (A cũ nhất). Duyệt B | Mở bài A, tức bài cũ nhất còn lại. Khớp thứ tự trong tab | P2 | |
| REV-24 | Rời trang khi đang sửa | Sửa nội dung → bấm sang bài khác | Trình duyệt hỏi "rời trang?". Bấm Duyệt hoặc Từ chối thì không hỏi | P1 | |
| REV-25 | Gửi lại form cũ | Duyệt → bấm Back → gửi lại form | Không duyệt lần hai: báo bài không còn lượt chờ duyệt. Link hết hạn thì WordPress báo hết hạn | P1 | |
| REV-26 | Ký tự đặc biệt trong nội dung sửa | Gõ emoji, xuống dòng, `&`, `<b>` | Bài đăng đúng chữ. Thẻ HTML bị loại. Không bị mã hoá hai lần **[Suy luận]** | P2 | |

### H. Lịch đăng (SCH)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| SCH-01 | Ngoài khung giờ | Admin tạm đặt khung giờ kết thúc trước giờ hiện tại → đăng bài → **khôi phục** | Giờ đề xuất = giờ mở khung của ngày hôm sau. Log ghi "Sau khung giờ đăng, dời sang…" | P1 | |
| SCH-02 | Đủ số bài trong ngày | Tạm đặt tối đa 1 bài/ngày → đăng 2 bài → **khôi phục** | Bài thứ 2 bị dời sang ngày hôm sau, log có lý do | P2 | |
| SCH-03 | Khoảng cách tối thiểu | Đăng 2 bài liền nhau | Cùng kênh cách nhau ít nhất 30 phút. Khác kênh thì không ràng buộc | P1 | |
| SCH-04 | Chế độ Tự đăng | Admin bật Tự đăng → đăng bài → **trả về Duyệt bắt buộc** | AI viết xong thì lượt đi thẳng sang *Đã lên lịch*, không qua *Chờ duyệt*. Đăng đúng giờ | P1 | |
| SCH-05 | Hiển thị múi giờ | So giờ trên chip, thẻ, Nhật ký chi tiết với giờ thật | Đều theo giờ website (GMT+7). Không lệch 7 tiếng | P1 | |
| SCH-06 | Lượt đã lên lịch nhưng quá giờ | Lượt *Đã lên lịch* quá giờ 15 phút mà chưa có kết quả (giả lập: dừng n8n trước giờ đăng) | Hiện cảnh báo "Quá giờ đăng dự kiến 15 phút…". Bài vào tab *Lỗi*. Có nút "Đăng lại nội dung đã duyệt" và "Cho AI viết lại", kèm nhắc kiểm tra kênh trước | P1 | |

### I. Đăng lên kênh (CH)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| CH-01 | Facebook, bài có ảnh | Sau REV-01, mở bài bằng cửa sổ ẩn danh | Bài dạng ảnh kèm chú thích, công khai. Link về bài gốc mở được | P0 | |
| CH-02 | Facebook, bài không ảnh | Duyệt D2 (chỉ Facebook) | Bài dạng bài kèm link | P1 | |
| CH-03 | Pinterest | Sau REV-01, mở Pin | Pin có ảnh, tiêu đề, mô tả, link đích đúng bài, nằm ở board mặc định "Sức khoẻ & Dinh dưỡng" (hoặc board theo bảng ghép danh mục) | P0 | |
| CH-04 | Hai kênh độc lập | Làm nhánh Pinterest lỗi (ví dụ tạm làm sai URL Make) → duyệt bài | Facebook vẫn đăng. Chỉ Pin bị *Lỗi*, có nội dung lỗi nguyên văn | P1 | |
| CH-05 | UTM trong link | Xem link trong bài Facebook và Pin | **[Chưa xác minh]** PRD mục 3 yêu cầu `utm_source=facebook` hoặc `pinterest`. Ghi lại thực tế | P2 | |

### J. Lỗi và đồng bộ lại (ERR)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| ERR-01 | n8n tắt khi đăng bài | Dừng container n8n → đăng bài → bật lại n8n → "Đồng bộ lại" | Lượt *Lỗi* có lý do không kết nối được n8n. Tab *Lỗi* có số. Đồng bộ lại thì AI viết lại, lượt về *Cần duyệt*. Duyệt xong thì *Đã đăng* | P0 | |
| ERR-02 | Lỗi sau khi đã duyệt: gửi lại nội dung đã duyệt | Lượt đã duyệt rồi bị lỗi khi đăng (ví dụ CH-04) → bấm "Đăng lại nội dung đã duyệt" | Có 2 nút: "Đăng lại nội dung đã duyệt" (nút chính) và "Cho AI viết lại". Bấm nút chính thì không gọi AI, không phải duyệt lại, đăng đúng bản người duyệt đã sửa | P0 | |
| ERR-03 | Cho AI viết lại | Cùng tình huống → bấm "Cho AI viết lại" | Hộp xác nhận có câu "AI sẽ viết lại từ đầu: nội dung người duyệt đã sửa sẽ bị thay và bài phải duyệt lại." Lượt về *Cần duyệt* với nội dung mới | P1 | |
| ERR-04 | Gửi lại Pin khi bài đã mất ảnh | Gỡ ảnh → "Đăng lại nội dung đã duyệt" cho lượt Pin | Báo "Bài không có ảnh đại diện nên không đăng được Pin. Thêm ảnh rồi thử lại." | P2 | |
| ERR-05 | Thêm ảnh rồi đồng bộ lại Pin | D2: thêm ảnh đại diện → Trung tâm → "Đồng bộ lại" ở thẻ Pinterest | Lượt Pin qua AI rồi về *Chờ duyệt*. Lượt Facebook giữ trạng thái cũ. Duyệt thì Pin được đăng | P1 | |
| ERR-06 | Quyền đăng lại | `nguoicham` xem lượt *Đã đăng* hoặc *Đã từ chối*. Sau đó admin xem | `nguoicham` thấy "Chỉ Administrator được đăng lại", không có nút. Admin có nút "Đăng lại", kèm xác nhận "ĐÃ được đăng… tạo thêm một bài nữa" | P1 | |
| ERR-07 | Đang xử lý thì không có nút | Lượt đang chờ, đang tạo, hoặc đã lên lịch nhưng chưa quá giờ | Ghi "Đang xử lý", không có nút | P1 | |
| ERR-08 | Treo trước khi gửi | Tạo lượt *pending* với số lần gửi = 0 và cũ hơn 2 phút (ví dụ chặn tiến trình nền) | Cảnh báo "Hơn 2 phút chưa gửi được sang n8n…". Có nút đồng bộ lại kèm xác nhận | P2 | |
| ERR-09 | n8n lỗi giữa chừng | Làm một khối n8n lỗi sau khi đã nhận bài | Trong vài giây lượt chuyển *Lỗi*. Lỗi ghi tên khối và số lần chạy | P1 | |
| ERR-10 | Bấm Đồng bộ lại 2 lần | Mở cùng một lượt lỗi ở 2 tab. Bấm "Cho AI viết lại" ở tab 1, rồi ở tab 2. Thử cả với "Đăng lại nội dung đã duyệt" | *(PM: R2 đã sửa)* Tab 2 báo "Lượt vừa đổi trạng thái…". Trong log và trên n8n chỉ có **1** lần gửi. Không đăng trùng | P1 | |
| ERR-11 | Đồng bộ lại bài bị chặn ở WordPress | Mở D6 (Tin nội bộ) và D7 (tên file PDF) trong Trung tâm và trong Nhật ký chi tiết | *(PM: R1 đã sửa)* **Không có** nút đồng bộ lại. Thay bằng dòng "Bị chặn ở WordPress: <lý do>. Gỡ bài khỏi danh mục chặn rồi mới đồng bộ được" | P1 | |
| ERR-11b | Máy chủ chặn khi bị gọi thẳng | Gửi POST `socialsync_resync` cho lượt của D6, dùng nonce lấy từ một lượt khác qua DevTools (hoặc sửa `job_id` trong form) | Máy chủ từ chối (vì nonce gắn với job_id, hoặc vì `block_reason`). Không gửi gì sang n8n. Log có lý do | P1 | |
| ERR-11c | Gỡ danh mục chặn rồi đồng bộ lại | D6: bỏ danh mục "Tin nội bộ", lưu bài → Trung tâm | Nút đồng bộ lại hiện ra. Bấm thì AI viết, bài về Chờ duyệt | P2 | |
| ERR-11d | AI phân loại Không đồng bộ (khác với bị chặn ở WordPress) | Xem D5 | Vẫn có nút "Đồng bộ lại" (thiết kế UX 3.5), kèm cảnh báo AI có thể lại phân loại Không đồng bộ. **[Cần PM chốt]** cảnh báo này có trong bản sửa không | P2 | |
| ERR-12 | Thông báo sau đồng bộ lại | Bấm đồng bộ lại | Thông báo "Trang tự cập nhật khi có kết quả". Trang tự cập nhật thật (xem JS-01) | P1 | |
| ERR-13 | Câu chỉ dẫn trong lý do bỏ qua Pin | Nạp luồng mới → đăng một bài không ảnh mới → đọc lý do | *(PM: R6 đã sửa)* Lý do ghi "Trung tâm SocialSync", không còn "Nhật ký đồng bộ". Bài D2 tạo trước khi nạp luồng vẫn giữ câu cũ: chấp nhận | P2 | |

### K. Cột "Mạng xã hội" trong danh sách bài (COL)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| COL-01 | Danh sách Bài viết | Bài viết → Tất cả bài viết | Cột "Mạng xã hội" ngay sau Tiêu đề, có chip FB và Pin với trạng thái và giờ. Bấm chip thì mở Trung tâm đúng bài | P1 | |
| COL-02 | Danh sách Sản phẩm | Sản phẩm → Tất cả | Cột nằm sau cột Tên. Hành vi giống COL-01 | P1 | |
| COL-03 | Bài chưa có lượt | Bài cũ hoặc bài nháp | Hiện "—" | P2 | |
| COL-04 | Ẩn cột | Mở "Tùy chọn hiển thị" → bỏ tick cột | Cột ẩn và được nhớ | P2 | |
| COL-05 | Hiệu năng | Đặt hiển thị 100 bài/trang | Log `hub.column` chỉ 1 lần mỗi lần tải trang (1 truy vấn) | P2 | |

### L. Tự cập nhật và phần chạy trên trình duyệt (JS)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| JS-01 | Tự cập nhật khi có việc đang chạy | Đăng bài mới → mở Trung tâm, không chạm gì | Trang tự tải lại khoảng mỗi 15 giây. Chip chuyển từ *AI đang viết* sang *Chờ duyệt* mà không phải bấm F5 | P0 | |
| JS-02 | Không mất chữ đang gõ | Khi trang đang tự cập nhật, gõ vào ô Facebook rồi chờ 30 giây | Trang **không** tải lại, chữ còn nguyên. Console có dòng "tạm dừng tự cập nhật" | P0 | |
| JS-03 | Tắt tự cập nhật | Bỏ tick "Tự cập nhật" → tải lại trang | Ngừng tự tải. Lựa chọn được nhớ (lưu ở bộ nhớ trình duyệt) | P1 | |
| JS-04 | Không có việc đang chạy | Mọi lượt đã xong | Trang không tự tải lại | P1 | |
| JS-05 | Tắt JavaScript | Tắt JS → duyệt, từ chối, tìm, chuyển tab | Mọi thao tác vẫn làm được. Máy chủ vẫn chặn đúng (REV-08, REV-12) | P1 | |
| JS-06 | Console sạch | Mở Console ở Trung tâm, danh sách bài, trình soạn | Không có lỗi màu đỏ. Chỉ có dòng debug `[SocialSync hub]` | P2 | |

### M. Giao diện, màn hình nhỏ, trợ năng (UI)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| UI-01 | Màu trạng thái thống nhất | So chip ở Trung tâm với nhãn ở Nhật ký chi tiết | Màu đỏ chỉ dùng cho *Lỗi*. *Đã từ chối* màu tím xám `#6b5b95` ở cả hai trang | P1 | |
| UI-01b | Thứ tự kênh thống nhất | Xem chip ở danh sách Trung tâm, form duyệt, thẻ kết quả, cột "Mạng xã hội", bảng trong trình soạn, Nhật ký | *(PM)* Facebook luôn đứng trước Pinterest ở mọi chỗ | P2 | |
| UI-02 | Màn hình hẹp | Thử ở 1100px, 960px, 768px, 375px | Từ 960px trở xuống, danh sách và chi tiết xếp chồng. Chọn bài thì cuộn tới chi tiết. Nút "← Danh sách" đưa về ô tìm. Không có thanh cuộn ngang | P1 | |
| UI-03 | Phân biệt được khi không nhìn màu | Bật giả lập mù màu trong DevTools | Chip vẫn phân biệt được nhờ biểu tượng và chữ | P2 | |
| UI-04 | Dùng bằng bàn phím | Chỉ dùng phím Tab, Enter | Đi qua được tab lọc, dòng bài, các nút duyệt. Thấy rõ ô đang chọn. Tab đang mở có `aria-current` | P2 | |
| UI-05 | Tên bài dài, tiếng Việt | Tên bài dài 150 ký tự | Xuống dòng gọn, không vỡ bố cục. Dấu tiếng Việt hiển thị đúng | P2 | |
| UI-06 | Trình duyệt | Chrome, Edge, Firefox. Thêm Safari iOS nếu có máy | Không lệch bố cục. Ô ngày giờ dùng được | P2 | |
| UI-07 | Thông báo | Sau mỗi thao tác, bấm F5 | Thông báo chỉ hiện một lần, không lặp lại sau F5 | P2 | |

### N. Báo cáo, Nhật ký chi tiết, Chạy thử (RPT)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| RPT-01 | Nhật ký chi tiết | Mở trang, lọc theo ngày, kênh, trạng thái | Lọc đúng. Bảng tổng hợp khớp số trong Trung tâm (Nhật ký đếm **lượt**, Trung tâm đếm **bài**). Có link "mở trong Trung tâm" | P1 | |
| RPT-02 | Từ Nhật ký sang Trung tâm | Bấm "Xem và duyệt" ở một lượt chờ duyệt | Mở Trung tâm đúng bài | P1 | |
| RPT-03 | Đồng bộ lại từ Nhật ký | Bấm đồng bộ lại ở Nhật ký | Quay về Nhật ký kèm thông báo | P2 | |
| RPT-04 | Báo cáo | Mở Báo cáo → bấm tạo báo cáo → bấm lại ngay | Lần 1: "n8n đang tạo báo cáo…". Lần 2 trong vòng 2 phút: cảnh báo đợi. Khoảng 30 giây sau có báo cáo tuần. Số liệu khớp Nhật ký | P1 | |
| RPT-05 | Chạy thử doppelherz.vn | Chạy 5 bài, rồi chạy với "Bỏ qua 5" | Ra kết quả phân loại và nội dung. **Không tạo lượt đồng bộ, không đăng gì.** Bấm lại trong 2 phút thì bị khoá | P1 | |

### O. Cấu hình (CFG, chỉ admin)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| CFG-01 | Gửi thử sang n8n | Bấm "Gửi thử sang n8n". Sau đó sửa webhook sai rồi thử lại → **khôi phục** | Lần đúng: "thành công: HTTP 200 … mã lượt chạy". Lần sai: thông báo lỗi rõ ràng | P1 | |
| CFG-02 | Đổi chế độ có hiệu lực ngay | Đổi Duyệt bắt buộc ↔ Tự đăng → **trả về Duyệt bắt buộc** | Thanh đầu Trung tâm và bảng trong trình soạn đổi theo ngay | P1 | |
| CFG-03 | Không lộ mã bí mật | Xem ô mã bí mật và 40 dòng log cuối | Chỉ hiện nguồn của mã và số ký tự. Log không có mã bí mật hay token | P1 | |
| CFG-04 | Danh mục chặn | Ghi " tin NỘI bộ " (thừa khoảng trắng, lẫn hoa thường) | Vẫn chặn được bài thuộc "Tin nội bộ" | P2 | |

### P. Bảo mật và dữ liệu (SEC)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| SEC-01 | REST cần mã bí mật | `curl -X POST …/wp-json/social-sync/v1/jobs/1`: lần 1 không có header, lần 2 có header sai | Cả hai lần trả 401. Log `rest.auth_failed` không ghi giá trị mã | P0 | |
| SEC-02 | Chuyển trạng thái sai | Gửi kèm mã đúng `{"status":"pending"}` vào một lượt đã đăng | Trả 409 | P1 | |
| SEC-03 | Dữ liệu sai | Gửi `{"visibility":"abc"}` | Trả 422 kèm danh sách vấn đề | P2 | |
| SEC-04 | Chống giả mạo yêu cầu (CSRF) | Gửi form duyệt hoặc đồng bộ lại với nonce sai hoặc thiếu | Bị chặn, không đổi trạng thái | P1 | |
| SEC-05 | Author gọi thẳng thao tác | test-author tự gửi POST `admin-post.php?action=socialsync_resync` | Bị chặn (nonce hoặc 403) | P1 | |
| SEC-06 | XSS qua nội dung và lỗi | Nội dung AI hoặc lỗi n8n có `<script>` (gửi qua REST với mã đúng) | Hiển thị thành chữ ở Trung tâm và Nhật ký, không chạy | P1 | |
| SEC-07 | Tham số URL lạ | `tab=abc`, `post_id=-5`, `post_id=abc`, `s=<script>`, `paged=999` | Không lỗi. Tab lạ thì về mặc định. Ký tự lạ được escape. Trang không tồn tại thì hiện danh sách rỗng | P1 | |
| SEC-08 | File log không mở được từ ngoài | Thử mở đường dẫn log qua trình duyệt | Không mở được, vì log nằm ngoài webroot | P2 | |
| SEC-09 | Quyền đọc trạng thái qua `admin-ajax` | Gọi `admin-ajax.php?action=socialsync_post_status&post_id=<bài của admin>`: lần 1 khi chưa đăng nhập, lần 2 bằng test-author, lần 3 bằng `nguoicham` | Chưa đăng nhập và Author **không** đọc được trạng thái bài của người khác (lỗi hoặc rỗng). Shop manager đọc được. Có kiểm tra nonce **[Cần PM chốt]** | P1 | |
| SEC-10 | Nonce và quyền của Đổi giờ / Hủy lịch | Gửi POST đổi giờ hoặc hủy lịch: thiếu nonce, nonce của lượt khác, bằng test-author | Đều bị chặn, lượt không đổi | P1 | |

### Q. Hồi quy và tài liệu (REG, DOC)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| REG-01 | Bài mẫu cho người chấm còn nguyên | Kết thúc mọi vòng test | #82 và #86 vẫn ở tab *Cần duyệt*. Nội dung không bị ai sửa | P0 | |
| REG-02 | Không lỗi PHP | `php -l` mọi file. Bật `WP_DEBUG_LOG`, đi qua mọi trang của plugin | Không có lỗi nghiêm trọng, warning hay notice nào mới | P0 | |
| REG-03 | Tắt rồi bật lại plugin | Tắt → bật lại | Không mất dữ liệu. Không lỗi khi kích hoạt | P2 | |
| DOC-01 | Hướng dẫn cho người chấm khớp màn hình mới | Đọc `nop-bai/06-san-pham-chay-that.md` mục 2–5 (PM sửa cuối cùng, sau khi code ổn định) | Không còn "SocialSync → Chờ duyệt", "Xem và duyệt", "SocialSync → Nhật ký đồng bộ, tải lại". Làm theo đúng từng bước trên giao diện mới thì không bị lạc. Mục 4 không ghi "đã kiểm chứng" cho thứ chưa chạy lại trên bản mới. Mục 5 (giới hạn) có giới hạn 2.000 lượt và những gì nhóm S chưa làm được | P0 | |
| DOC-02 | Sơ đồ màn hình | `nop-bai/04-so-do-quy-trinh.md` mục A5 | Bảng màn hình và wireframe là Trung tâm, không còn 4 màn cũ | P1 | |
| DOC-03 | Chữ hướng dẫn trong sản phẩm | Grep các cụm "Chờ duyệt", "Nhật ký đồng bộ" trong `assets/`, `includes/`, luồng n8n | Không còn chỉ dẫn tới màn đã bỏ (EDT-05, ERR-13) | P2 | |

### S. Đổi giờ và hủy lịch sau khi duyệt (S) — PM đang làm

Thiết kế PM mô tả:
- Thẻ "Đã lên lịch" trong Trung tâm có ô giờ cùng 2 nút "Đổi giờ" và "Hủy lịch, trả về Chờ duyệt".
- Khi tới giờ, n8n **hỏi lại WordPress** trước khi đăng, gửi kèm mã lượt và mã lệnh.
- WordPress chỉ trả "đăng" khi lượt còn ở trạng thái `scheduled` **và** mã lệnh khớp.

Tất cả testcase nhóm này dùng giờ đặt xa nên không đăng công khai. Riêng S-03 và S-06 có lần đăng thật.

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| S-01 | Thẻ đã lên lịch có điều khiển | Duyệt theo giờ = bây giờ + 3 tiếng → xem thẻ | Có ô giờ (điền sẵn giờ hiện tại của lượt), nút "Đổi giờ", nút "Hủy lịch, trả về Chờ duyệt". Thanh đầu, chip và dòng thời gian khớp giờ | P0 | |
| S-02 | Đổi giờ sang muộn hơn | S-01 → đổi sang giờ cũ + 1 tiếng → Đổi giờ | Chip và thẻ hiện giờ mới. Dòng thời gian ghi "đổi giờ HH:MM → HH:MM bởi <tên>". Log có `job_id` và mã lệnh mới | P0 | |
| S-03 | Lệnh cũ không đăng, lệnh mới đăng đúng giờ *(1 lần đăng thật, dùng giờ ngắn)* | Duyệt theo giờ = bây giờ + 5 phút → đổi sang bây giờ + 10 phút → theo dõi Fanpage và n8n | Ở phút thứ 5: n8n hỏi WordPress, mã lệnh không khớp, lần chạy kết thúc **không đăng**, có ghi lý do. Ở phút thứ 10: đăng **đúng 1 bài** | P0 | |
| S-04 | Đổi giờ sang sớm hơn | Lượt hẹn bây giờ + 3 tiếng → đổi sang bây giờ + 5 phút | Đăng lúc bây giờ + 5 phút. Lệnh cũ (hẹn 3 tiếng) tới giờ thì không đăng nữa. Để không phải ngồi chờ, có thể hủy luôn sau khi đăng | P1 | |
| S-05 | Hủy lịch thì không đăng | Duyệt theo giờ = bây giờ + 5 phút → Hủy lịch → chờ qua giờ cũ thêm 2 phút | Lượt về **Chờ duyệt**, bài quay lại tab *Cần duyệt*, số trên menu tăng 1. Tới giờ cũ, n8n hỏi rồi **không đăng**. Fanpage và Pinterest **không có bài**. *(Phải đạt trước khi dùng cách "duyệt giờ xa rồi hủy" cho các testcase khác)* | P0 | |
| S-06 | Hủy rồi duyệt lại không bị trùng *(1 lần đăng thật)* | Duyệt +5 phút → Hủy lịch → duyệt lại "Duyệt và đăng ngay" → chờ qua giờ cũ | Đăng ngay **1 bài**. Tới giờ cũ, lệnh cũ không đăng thêm bài nào | P0 | |
| S-07 | Nội dung sau khi hủy | Sửa tiêu đề Pin → duyệt giờ xa → Hủy lịch → mở form duyệt | **[Cần PM chốt]** Đề xuất: form giữ nội dung **đã duyệt**, gồm cả phần đã sửa, không gọi AI lại. Dòng thời gian ghi ai hủy, lúc nào | P1 | |
| S-08 | Hủy hoặc đổi giờ sát giờ đăng | Hẹn bây giờ + 2 phút. Bấm Hủy vào giây thứ 110–120 | Chỉ một trong hai kết quả: (a) hủy thành công và không có bài; (b) bài đã đăng, nút Hủy báo "Lượt vừa đổi trạng thái / đã đăng", lượt ở *Đã đăng*. **Không bao giờ** vừa ở *Chờ duyệt* vừa có bài trên kênh | P1 | |
| S-09 | WordPress không trả lời khi n8n hỏi lại | Hẹn +5 phút → trước giờ đó làm hỏng đường gọi về (ví dụ đổi mã bí mật phía n8n, hoặc dừng WordPress 2 phút) → khôi phục | **[Cần PM chốt]** Đề xuất: không hỏi được thì **không đăng** (an toàn hơn), lượt chuyển *Lỗi* với lý do "không xác nhận được với WordPress", có thể "Đăng lại nội dung đã duyệt" | P1 | |
| S-10 | Hai người thao tác cùng lúc | Admin bấm Đổi giờ, `nguoicham` bấm Hủy lịch gần như cùng lúc | Chỉ một thao tác có hiệu lực. Người còn lại được báo ai đã xử lý. n8n đăng đúng theo thao tác thắng (hoặc không đăng gì) | P1 | |
| S-11 | Đổi sang giờ quá khứ hoặc ngoài khung giờ | Đổi sang giờ hôm qua. Đổi sang 23:30 (ngoài khung 07:00–22:00). Đổi vào ngày đã đủ số bài | **[Cần PM chốt]** Giờ quá khứ: báo lỗi hay đăng ngay? Ngoài khung giờ hoặc quá số bài: tôn trọng giờ người chọn (giống chọn tay ở trình soạn) hay dời theo bộ xếp lịch? Cảnh báo phải hiện trước khi lưu | P1 | |
| S-12 | Lượt đã chờ sẵn trong n8n từ luồng **cũ** | Có lượt được duyệt **trước khi** nạp luồng mới (bản cũ không hỏi lại) → Hủy lịch | **[Chưa xác minh]** Lần chạy đang chờ có thể vẫn dùng luồng cũ và đăng mà không hỏi. Trước khi demo: liệt kê các lần chạy đang chờ (waiting) trên n8n và dừng chúng, hoặc cho các lượt đó đăng hết. Ghi vào điều kiện E9 | P0 | |
| S-13 | Bài đã đăng hoặc đang đăng | Xem thẻ *Đã đăng* | Không có ô giờ, nút Đổi giờ hay nút Hủy | P1 | |
| S-14 | Hủy lịch ở chế độ Tự đăng | Admin bật Tự đăng → đăng bài → lượt *Đã lên lịch* → Hủy lịch → **trả về Duyệt bắt buộc** | **[Cần PM chốt]** Lượt về *Chờ duyệt* (tab *Cần duyệt* hiện lại, theo Q1), hay có trạng thái riêng "Đã hủy"? | P2 | |
| S-15 | Đổi giờ một kênh không ảnh hưởng kênh kia | Bài có FB và Pin cùng *Đã lên lịch* → chỉ hủy Pin | FB giữ lịch và đăng đúng giờ. Pin về *Chờ duyệt* | P1 | |
| S-16 | Log hai phía | Sau S-03 và S-05 | Log WordPress có bước n8n hỏi lại (mã lượt, mã lệnh, câu trả lời đăng hoặc không đăng). Trên n8n thấy được nhánh "không đăng" và lý do | P1 | |

### R. Kịch bản đầu cuối, đi như người chấm (E2E)

| ID | Kịch bản | Các bước | Kết quả mong đợi | Ưu tiên | KQ |
|---|---|---|---|---|---|
| E2E-01 | Người chấm thử trong 5 phút *(đăng thật)* | `nguoicham` làm đúng theo tài liệu 06: viết bài có ảnh → Đăng → vào Trung tâm → chờ trang tự cập nhật → Duyệt và đăng ngay → mở link Facebook bằng cửa sổ ẩn danh. **Đếm số lần bấm và bấm giờ** | Không phải bấm F5, không bị lạc. Số lần bấm vào khoảng mục tiêu 5 của thiết kế UX (trước đây 8–10) **[UX 4]**. Tổng thời gian dưới 3 phút | P0 | |
| E2E-02 | Duyệt liên tiếp 3 bài | Có 3 bài chờ duyệt → duyệt lần lượt với tick "mở bài tiếp theo", 2 bài từ chối, 1 bài duyệt | Tổng số lần bấm khoảng N + 1 **[UX 4]**. Không quay về danh sách giữa chừng | P0 | |
| E2E-03 | Sản phẩm *(đăng thật)* | `nguoicham` đăng D3 → duyệt và đăng ngay | Bài trên Facebook có giá và câu khuyến cáo. Pin có câu khuyến cáo | P0 | |
| E2E-04 | Lỗi rồi khôi phục | Tạo lỗi Pin (CH-04) → khôi phục → tab *Lỗi* → "Đăng lại nội dung đã duyệt" | Làm xong trong 1 màn hình, khoảng 3–4 lần bấm **[UX 4]**. Kết thúc ở *Đã đăng* | P1 | |
| E2E-05 | Bài không ảnh từ đầu đến cuối | Đăng D2 → duyệt chỉ Facebook → thêm ảnh → đồng bộ lại Pin → duyệt | Facebook đăng ngay ở lần duyệt đầu. Pin đăng sau khi đã thêm ảnh. Dòng thời gian ghi đủ các bước | P1 | |

---

## 4. Thứ tự chạy theo thời gian còn lại

| Vòng | Nội dung | Testcase | Ước lượng **[Suy luận]** |
|---|---|---|---|
| 0 | Điều kiện vào | E1–E9, REG-02 | 10 phút |
| 0b | Chỉ đọc, chạy được trước "SẴN SÀNG CHẠY" | NAV-01–NAV-07, HUBL-01, HUBL-03, HUBL-04, HUBL-10, HUBD-01, UI-01, UI-01b, UI-02, SEC-01, SEC-07, SEC-09 | 20 phút |
| 1a, an toàn lịch (P0) | Chứng minh hủy lịch có tác dụng **trước** khi dựa vào nó | S-12, S-01, S-02, S-05, S-03, S-06 | 30 phút (phần lớn là chờ giờ) |
| 1b, smoke (P0) | Luồng người chấm | PUB-01, PUB-02, PUB-03, EDT-05, AI-01, AI-02, HUBD-05, HUBD-06, REV-01, REV-02, REV-03, REV-07, REV-10, CH-01, CH-03, ERR-01, ERR-02, JS-01, JS-02, E2E-01, E2E-02, E2E-03, DOC-01, REG-01 | 60 phút |
| 2 (P1) | Luồng lỗi, phân quyền, trường hợp đặc biệt | Các testcase P1 còn lại. Ưu tiên các mục PM vừa sửa: ERR-10, ERR-11, ERR-11b, REV-12, REV-12b, REV-15, HUBL-15b, REV-21b, S-08, S-09, S-10, SEC-10 | 90 phút |
| 3 (P2) | Nếu còn giờ | Các testcase P2 | Tuỳ thời gian |

**Tiêu chí kết thúc trước khi nộp:**
- 100% testcase P0 đạt.
- Không còn lỗi P1 nào mở trên đường người chấm đi (nhóm NAV, REV, ERR, DOC).
- Các mục **[Cần PM chốt]** đã có quyết định: sửa, hoặc ghi vào phần "Giới hạn" của tài liệu 06.
- Cấu hình đã trả về mặc định (Duyệt bắt buộc). #82 và #86 vẫn ở *Cần duyệt*.

---

## 5. Điểm rủi ro rút ra khi đọc code (cần chạy để xác nhận)

**Tình trạng lúc ~10:10 (theo trả lời của PM):**
- R1–R6: PM nhận **sửa cả 6**. Chưa kiểm lại; Tester kiểm khi có "SẴN SÀNG CHẠY".
- R7 (tài liệu 06): PM sửa cuối cùng, sau khi code ổn định.
- R8 (tìm không dấu): PM sửa.
- R9 (tab Cần duyệt ở chế độ Tự đăng): PM sửa theo thiết kế.
- R10 (giới hạn 2.000 lượt): chấp nhận, ghi vào lộ trình.
- R11 (luật con số): chưa có trả lời, giữ ghi vào giới hạn.

**Rủi ro mới của nhóm S:**
- **R12 [P0]:** Lượt đang chờ trong n8n từ luồng cũ có thể đăng dù đã hủy (S-12).
- **R13 [P1]:** Chưa rõ xử lý khi n8n hỏi WordPress mà không có câu trả lời (S-09).
- **R14 [P1]:** Hủy sát giờ có thể ra trạng thái mâu thuẫn (S-08).

Bảng gốc (bản 1.0):

| # | Điểm | Testcase | Nguồn | Mức |
|---|---|---|---|---|
| R1 | Đồng bộ lại một bài bị chặn theo danh mục có thể đẩy bài nội bộ sang AI rồi lên kênh | ERR-11 | `handle_resync()` gọi thẳng `SocialSync_Dispatcher::kick()`. Không chỗ nào gọi lại `block_reason()` (chỉ có ở `class-socialsync-publish-hook.php:99`) | P1, **[Suy luận]** |
| R2 | Bấm "Đồng bộ lại" hai lần nhanh có thể gửi n8n hai lần | ERR-10 | Nhánh viết lại dùng `update_jobs` không điều kiện. Nhánh gửi lại nội dung đã duyệt thì dùng `transition` có điều kiện | P1, **[Suy luận]** |
| R3 | Lỗi kiểm tra form duyệt làm mất phần người duyệt vừa sửa | REV-12 | `SocialSync_Review::back()` chuyển trang, form dựng lại từ dữ liệu cũ | P1, Cần PM chốt |
| R4 | Ô giờ để trống mà bấm "Duyệt theo giờ" thì đăng ngay | REV-15 | `handle()`: `$ts = time()` khi ô `at` rỗng | P1, Cần PM chốt |
| R5 | Chữ trong trình soạn còn chỉ tới màn "Chờ duyệt" đã bỏ | EDT-05 | `assets/schedule-panel.js:50`, `class-socialsync-schedule-ui.php:9` | P1 (chữ) |
| R6 | Lý do bỏ qua Pin (do n8n viết) chỉ tới "Nhật ký đồng bộ" | ERR-13 | Khối "AI: kiểm tra và áp nội dung" trong `socialsync-main.json` | P2 |
| R7 | Tài liệu cho người chấm (06) còn mô tả màn cũ | DOC-01 | `nop-bai/06-san-pham-chay-that.md` mục 2 | **P0** |
| R8 | Tìm không dấu không ra kết quả | HUBL-10 | `mb_stripos` trong `SocialSync_Hub::render()` | P2 |
| R9 | Chế độ Tự đăng vẫn hiện tab Cần duyệt (khác thiết kế UX 3.5) | HUBL-02 | `render_tabs()` | P2 |
| R10 | Trung tâm chỉ đọc 2.000 lượt mới nhất | HUBL-17 | `load_groups()` | P2 |
| R11 | PRD có luật kiểm con số nhưng n8n chưa làm | AI-10 | PRD US-008, khối kiểm tra trong n8n | P2, ghi vào giới hạn |

---

## 6. Kết quả chạy

### Vòng 0 và 0b: chỉ đọc (20/11, sau tin "SẴN SÀNG CHẠY" của PM)

Cách chạy:
- `php -l` trong container WordPress.
- `wp socialsync config/jobs`.
- `curl` vào `localhost:8088`.
- Edge headless (`cdp.py` của PM) đăng nhập `nguoicham` trên `https://shop.adaptivealgo.uk`, kịch bản `t_readonly.json`: chỉ mở trang và đọc DOM, không bấm nút gửi nào, không đổi dữ liệu.

| ID | KQ | Ghi nhận thực tế |
|---|---|---|
| E5 | Đạt | `php -l` sạch toàn bộ file PHP của plugin |
| E8 | Đạt | PM đã nhắn "SẴN SÀNG CHẠY". `hub.js` nạp bản `?ver=0.4.0.1795142717`, có đuôi chống cache |
| E9 | Đạt | Theo PM: 0 lần chạy đang chờ trước khi nạp luồng mới |
| REG-01 | Đạt (lúc bắt đầu) | #82 và #86: cả 2 kênh đều `needs_review`. Kiểm lại lúc kết thúc |
| NAV-01 | Đạt | Menu `nguoicham`: SocialSync (2), Trung tâm (2), Báo cáo, Nhật ký chi tiết, Chạy thử doppelherz.vn |
| NAV-02 | Đạt | Không có mục Cấu hình. Mở thẳng `?page=social-sync` thì nhận "Sorry, you are not allowed to access this page." |
| NAV-05 | Đạt | `socialsync-review` → `?page=socialsync&tab=review`. Thêm `&post_id=74` → mở chi tiết #74 |
| NAV-06 | Đạt | `?page=socialsync-log` có tiêu đề "Nhật ký chi tiết" |
| HUBL-01 | Đạt | Có "Chế độ: Duyệt bắt buộc", khung 07:00–22:00, tối đa 10, cách ≥ 30 phút |
| HUBL-03 | Đạt | Mặc định mở tab *Cần duyệt* (có 2 bài), chọn sẵn bài cũ nhất (#82) |
| HUBL-04 | Đạt | Mỗi bài 1 dòng, chip FB đứng trước Pin |
| HUBL-08 | Đạt | Tab *Bỏ qua / Từ chối* = 5 bài (#90, #87, #80, #78, #76). #83 (FB đã đăng, Pin thiếu ảnh) không nằm trong tab |
| HUBL-10 | Đạt | "suc khoe" ra 3 bài có dấu. "OMEGA" ra 2 bài |
| HUBL-11 | Đạt | `#74` ra đúng 1 bài |
| HUBL-12 | Đạt | "Không có bài nào khớp “zzzz”", cột chi tiết có trạng thái trống |
| HUBD-01 | Đạt | #74: có phân loại kèm lý do, nguồn Claude (model, token, 15,9 giây), ảnh |
| HUBD-03 | Đạt | #90: "Không có" ảnh, giải thích, link "Thêm ảnh đại diện ↗" |
| HUBD-06 | Đạt | #74: FB và Pin "Đã đăng 19:24 19/11", có nút "Mở bài trên … ↗" |
| HUBD-09, UI-01 | Đạt | "Đã từ chối" màu `#6b5b95` ở cả Trung tâm và Nhật ký |
| REV-15b | Đạt | #82: giờ đề xuất 19/11 20:24 đã qua, thẻ có hộp vàng giải thích |
| ERR-06 (phần Shop manager) | Đạt | #74 đã đăng: `nguoicham` chỉ thấy nút "Mở bài", không có "Đăng lại" |
| ERR-11 | Đạt | #76 (Tin nội bộ): 0 nút ở Trung tâm. Có băng "Bị chặn ở WordPress…". Nhật ký chi tiết cũng có dòng giải thích thay cho nút |
| ERR-11d | Đạt | #78 (AI phân loại X): nút "Đồng bộ lại" có xác nhận "Lần trước AI phân loại bài này là Không đồng bộ…" |
| ERR-13 | Đạt có ghi chú | Dữ liệu cũ (#87, #90) vẫn ghi "Nhật ký đồng bộ", như đã dự kiến. Bài mới cần kiểm sau |
| CH-05 (Pin) | Đạt | Link Pin có `utm_source=pinterest&utm_medium=social&utm_campaign=socialsync`. Phần Facebook chưa kiểm |
| COL-01 | Đạt | Cột `socialsync` ngay sau `title`. Chip có link `?page=socialsync&post_id=…` |
| **COL-02** | **Lỗi P2** | Ở danh sách Sản phẩm, cột "Mạng xã hội" nằm **cuối bảng** (sau cột Ngày), không đứng sau "Tên" như danh sách Bài viết. Có lẽ WooCommerce thêm cột sau bộ lọc của plugin **[Suy luận]** |
| EDT-05c | Đạt | Bảng trong trình soạn bài #74: "Facebook Fanpage: Đã đăng 19:24 19/11 · mở ↗", Pinterest tương tự, có "Mở trong Trung tâm SocialSync →" |
| SEC-01 | Đạt | `status`, `jobs/99999`, `jobs/99999/go` khi không có hoặc sai mã: đều trả 401 |
| SEC-09 (chưa đăng nhập) | Đạt | `admin-ajax` trả `0` / 400, không lộ dữ liệu. Phần Author chưa kiểm |
| **SEC-07** | **Đạt một phần, lỗi P2** | Không bị chèn script. `tab=abc` thì về tab mặc định. Nhưng với `paged=999`, tab *Cần duyệt* hiện số **2** mà danh sách lại báo "Không còn bài nào chờ duyệt. 🎉", dễ hiểu nhầm. Nên đưa về trang cuối hoặc báo "Trang không tồn tại" |
| UI-02 | Đạt (375px) | Xếp một cột, không tràn ngang. Ảnh: `scratchpad/out/ro-06-w375.png` |
| JS-06 | Đạt | 0 lỗi trên console qua 15 trang. Chỉ có các dòng debug `[SocialSync hub]` và `[SocialSync panel]` |

**Chưa chạy, vì làm đổi dữ liệu hoặc đăng công khai, đang chờ chủ dự án đồng ý:** nhóm PUB, AI, REV, S, ERR (trừ các mục ở trên), E2E, cùng các ca cần đổi cấu hình.

### Vòng 1–2: có đổi dữ liệu (20/11, 09:58–10:16), không đăng công khai

Cách làm:
- Chủ dự án cho phép tạm đổi cấu hình và tối đa 2–3 lần đăng thật. **Thực tế không đăng thật lần nào.**
- Tạo 7 bài `[TEST]` bằng WP-CLI với tác giả `nguoicham`. Mọi thao tác duyệt, đổi giờ, hủy, từ chối làm bằng trình duyệt ẩn với tài khoản `nguoicham`.
- Tạm đổi cấu hình bằng `wp option patch` từng khoá một, và đã khôi phục. Bản cuối so với bản sao lưu: **không khác khoá nào** (giữ `board_map` của PM, `approval = required`).
- Để không có gì lên kênh: tạm đặt khung giờ 07:00–08:00, nên mọi bài thử tự xếp sang sáng 21–22/11.

| ID | KQ | Ghi nhận thực tế |
|---|---|---|
| SCH-01 | Đạt | Tạo bài lúc 09:59, ngoài khung 07:00–08:00 → giờ đề xuất 07:00 21/11 |
| SCH-03 | Đạt | Cùng kênh: 07:00, 07:30, 08:00. Bài thứ 4 dời sang 07:00 22/11 |
| PUB-01 | Đạt | 5 bài tạo qua WP-CLI đều có lượt và AI viết trong khoảng 15–40 giây |
| PUB-02 | Đạt | Cập nhật #92 đã xuất bản → `hook.skip` "bài đã xuất bản từ trước", vẫn 2 lượt |
| PUB-03 | Đạt | #95 về nháp rồi xuất bản lại → vẫn 2 lượt, không tạo lượt mới |
| AI-01 | Đạt | #92, #93, #94: phân loại E, Claude viết |
| AI-02 (nội dung mẫu) | Đạt | #95: Facebook có "Giá: 185.000 ₫" và câu khuyến cáo ở dòng riêng. Ca do Claude viết chưa chạy (xem L3) |
| AI-05 | Đạt | #94 không ảnh: Pin "Không đăng: thiếu ảnh" ngay khi AI viết xong |
| HUBL-05, HUBL-09 | Đạt | Cần duyệt 6. Thứ tự cũ nhất trước: #82, #86, #92, #93, #94, #95. #95 có cờ "Nội dung mẫu (AI lỗi)" |
| REV-12 | Đạt | Xoá trống ô Facebook rồi bấm Duyệt → không gửi; hộp "Facebook Fanpage: nội dung đang trống"; con trỏ đưa về ô đó |
| REV-15 | Đạt | Trên trình duyệt: "chưa chọn giờ đăng (hoặc bấm “Duyệt và đăng ngay”)". Phía máy chủ (bỏ qua JS): "Chọn giờ đăng cho Facebook Fanpage…", không đổi trạng thái |
| REV-06 | Đạt | Bỏ chọn cả 2 kênh → "Chưa chọn kênh nào để đăng…" |
| REV-12b | Đạt | Máy chủ: Facebook 5.001 ký tự → báo lỗi, điền lại bản đang sửa (tiêu đề Pin vừa sửa còn nguyên) kèm băng "Đã điền lại…". Mở lại lần 2 thì bản nháp không còn |
| REV-03 | Đạt | Sửa Facebook rồi duyệt → ghi chú "Người duyệt sửa: message". Kênh không sửa ghi "Duyệt nguyên văn" |
| REV-21b | Đạt | Duyệt xong mở bài kế tiếp (#82). Thông báo có link "Xem lại bài vừa xử lý →" trỏ về #92 |
| REV-10 | Đạt | Gửi lại form cũ → "Bài không còn lượt nào chờ duyệt: Người chấm đã duyệt lúc 10:03 20/11." |
| S-01 / HUBD-07 | Đạt | Thẻ "🕑 Đã lên lịch 07:00 21/11" có ô giờ cùng các nút Lưu giờ mới / Đăng ngay / Hủy lịch |
| S-11 | Đạt | Giờ quá khứ → "Giờ mới phải sau hiện tại ít nhất 1 phút…". 23:30 (ngoài khung) → đổi được, kèm "Lưu ý: giờ này nằm ngoài khung đăng…" |
| S-02 | Đạt | Đổi giờ FB sang 23:30: chip, thẻ và `dispatch_token` mới đều đúng. Dòng thời gian không ghi lần đổi giờ (xem L2) |
| S-15, S-07 | Đạt | Hủy riêng Pin → Pin về Chờ duyệt, giữ nội dung. FB vẫn giữ lịch. Ghi chú "Hủy lịch bởi Người chấm lúc 10:03…" |
| **S-03** | **Đạt** | #93 duyệt hẹn 10:08, rồi dời FB sang mai. 10:08:00 n8n hỏi lại → `go.stop token_mismatch` → **không đăng** |
| **S-05** | **Đạt** | #93 hủy Pin. 10:08:00 n8n hỏi lại → `go.stop status_needs_review` → **không đăng**. Fanpage và Pinterest không có bài |
| SCH-04, HUBL-02 | Đạt | Tự đăng: AI viết xong thì #97 đi thẳng sang "Đã lên lịch" 07:00 / 07:30. Thanh đầu "Chế độ: Tự đăng". Tab Cần duyệt vẫn hiện vì còn bài (Q1) |
| S-14 | Đạt | Hủy lịch ở chế độ Tự đăng → về Chờ duyệt, `dispatch_token` NULL |
| REV-20 | Đạt | URL lệnh đăng sai → "Không gửi được lệnh đăng sang n8n, lượt đã trả về Chờ duyệt: … HTTP 404 …" |
| ERR-01, HUBL-07, HUBD-08 | Đạt | Webhook sai → #98 cả 2 kênh Lỗi, lỗi hiện đầy đủ. Tab "Lỗi 1" có màu đỏ. Khôi phục rồi Đồng bộ lại → về Chờ duyệt |
| JS-01 | Đạt | Sau khi đồng bộ lại, trang tự tải sau 15 giây, chip đổi sang Chờ duyệt mà không bấm F5 |
| ERR-05 | Đạt một phần | #94: thêm ảnh rồi đồng bộ lại Pin → Pin về Chờ duyệt. **Nhưng nội dung là mẫu, không phải AI** (L1) |
| ERR-10 | Đạt, có ghi chú | 2 lần bấm cùng lúc → chỉ 1 lần gửi n8n (số lần gửi tăng 1 → 2). Lần thứ hai không có thông báo nào (L6) |
| HUBL-15b | Đạt, có ghi chú | #95 nháp: có băng giải thích. Gửi duyệt thì máy chủ chặn. **Nút Duyệt vẫn hiện** (L4) |
| REV-07, REV-08, REV-09 | Đạt | Nút Từ chối bị mờ khi chưa có lý do. Bấm lý do nhanh thì nút bật. Từ chối #95 → "Đã từ chối 2 lượt…", mở bài kế tiếp |
| COL-02 (chạy lại) | Đạt | Thứ tự cột: thumb, name, socialsync, sku |
| SEC-07 (chạy lại) | Đạt | `paged=999` → đưa về trang cuối, hiện 8 bài |
| REG-01 | Đạt | Cuối vòng: Cần duyệt = 2 (#82, #86), menu "SocialSync 2". Không còn lượt `scheduled` nào |

**Lỗi và phát hiện mới**

| Mã | Mức | Mô tả | Bằng chứng |
|---|---|---|---|
| **L1** | **P1** | **Đồng bộ lại một kênh luôn rơi về nội dung mẫu.** Claude chỉ viết cho kênh được gửi lại, nhưng khối kiểm tra trong n8n đòi đủ cả Facebook và Pinterest. Hệ quả: tốn 1 lần gọi AI, và nút "Cho AI viết lại" không bao giờ ra nội dung AI | 3/3 lần: lượt #72 (Pin), #81 (FB), #82 (Pin). Lý do ghi "AI không đạt: facebook.message dài 0 ký tự…", trong khi `tokens_out` là 1.040–1.346 |
| L2 | P2 | Dòng thời gian ghi lần hủy lịch thành "Pinterest: **duyệt** bởi … — Hủy lịch bởi …" và dùng giờ lúc duyệt. Không có dòng cho lần đổi giờ | #92 |
| L3 | P2 | Sản phẩm mà AI đề xuất "Không đồng bộ" bị ép thành loại Sản phẩm, nhưng AI không viết nội dung. Lý do hiện ra là "AI không đạt: … dài 0 ký tự", không nói lý do thật | #95 (tokens_out 281) |
| L4 | P2 | Bài không ở trạng thái Đã xuất bản vẫn hiện nút "Duyệt…". Máy chủ có chặn, nhưng nên ẩn hoặc làm mờ nút | #95 khi là nháp |
| L5 | P2, quan sát | Nhãn `[TEST]` trong tiêu đề làm AI coi bài là bài thử nội bộ: #96 bị phân loại X ("Tiêu đề có nhãn [TEST]…"), #95 không được viết nội dung. **Luồng thật đầu-cuối không nên dùng `[TEST]` trong tiêu đề** | #95, #96 |
| L6 | P2 | Bấm Đồng bộ lại lần 2 ngay sau lần 1: không có thông báo gì (thông báo "Lượt vừa đổi trạng thái" bị mất) | ERR-10 |
| L7 | P2, chữ | Hủy lịch ở chế độ Tự đăng vẫn báo "nội dung đã duyệt được giữ", dù chưa ai duyệt | S-14 |

**Chưa chạy:**
- *Để PM chạy trong luồng thật đầu-cuối* (cần đăng công khai): REV-01, REV-02 lúc tới giờ, CH-01, CH-03, CH-04, ERR-02 (đăng lại nội dung đã duyệt thì đăng ngay), S-06, E2E-01, E2E-03.
- *Cần thêm tài khoản Editor hoặc Author:* NAV-03, NAV-04, SEC-05, phần Author của SEC-09.
- *Còn lại chưa chạy:* S-08, S-09, S-10, SEC-04, AI-03, AI-06, AI-07, RPT-04, RPT-05, CFG-01–04, UI-03–06, EDT-01–04 (PM đã chạy EDT-02 và EDT-05).

**Bài thử đã tạo** (tất cả do `nguoicham` đăng, ảnh đại diện #60):

| Bài | Nội dung | Trạng thái cuối |
|---|---|---|
| #92 | Magie, kiến thức | FB và Pin đã từ chối |
| #93 | Chất xơ, kiến thức | FB và Pin đã từ chối |
| #94 | Vitamin B12, đã thêm ảnh khi test | FB và Pin đã từ chối |
| #95 | Sản phẩm Magie B6, giá 185.000 | FB và Pin đã từ chối |
| #96 | Thảo dược, có cụm từ rủi ro | Cả 2 kênh bỏ qua (AI phân loại X) |
| #97 | Canxi | FB và Pin đã từ chối |
| #98 | Vitamin A | FB và Pin đã từ chối |

Lý do từ chối khi dọn là "Dọn bài thử của Tester" (riêng #95 là "Bài nội bộ, không đăng mạng xã hội"). Không có bài nào lên Facebook hay Pinterest.

**Lưu ý cho n8n:** vẫn còn vài lần chạy n8n đang chờ tới sáng 21/11 (07:00, 07:30, 10:00, 23:30) từ các lệnh đã hủy hoặc đã dời. Tới giờ chúng sẽ hỏi lại WordPress và nhận `go:false`. Nếu cần nạp lại luồng trước giờ đó, có thể dừng các lần chạy này mà không mất gì, vì mọi lượt liên quan đã bị từ chối.

### Vòng 3: chạy lại sau khi PM sửa L1–L4, L7 (20/11, 10:20–10:28), không đăng công khai

Cách làm giống vòng trước: tạm đổi cấu hình, khôi phục xong so với bản sao lưu thì **không khác khoá nào**. Bài thử mới: #100 (bài viết), #101 (sản phẩm).

| Mã | KQ | Ghi nhận thực tế |
|---|---|---|
| **L1 (P1)** | **Đạt** | #100: đồng bộ lại riêng Facebook (lượt #83) và riêng Pinterest (#84) → đều ra "Claude viết (claude-opus-5-5, 1 lần gọi cho 1 kênh, ~13 giây)", `content_source = ai`, rồi về Chờ duyệt. **Lưu ý:** lần thử đầu khi tiêu đề còn "[TEST]" thì AI phân loại X (đúng như L5), nên tester tạm bỏ nhãn để kiểm, xong đặt lại |
| L2 | Đạt | Dòng thời gian: "Facebook Fanpage: duyệt bởi … · Đổi giờ sang 09:00 21/11 bởi Người chấm lúc 10:25 20/11" và "10:25:29 Pinterest: Hủy lịch bởi Người chấm lúc 10:25 20/11", đúng giờ hủy |
| L3 | Đạt | Sản phẩm #101 (AI đề xuất X): lý do "…AI đề xuất Không đồng bộ nên không viết nội dung; sản phẩm luôn được đăng, dùng nội dung mẫu để người duyệt sửa". Phần đầu vẫn là "Dùng mẫu cố định vì AI không đạt:" (chữ hơi thừa, không chặn) |
| L4 | Đạt | #101 ở dạng nháp: chỉ còn lý do nhanh và nút "Từ chối toàn bộ", kèm "Bài không còn xuất bản trên website nên chưa duyệt được… Vẫn có thể Từ chối." Từ chối thì được |
| L7 | Đạt | Tự đăng → hủy lịch: "…nội dung AI đã viết được giữ nguyên; duyệt khi muốn đăng." |
| L6 | Ghi vào giới hạn | Theo quyết định của PM |
| REG-01 | Đạt | Cần duyệt = 2 (#82, #86). Không còn lượt `scheduled` |

**Lỗi mới**

| Mã | Mức | Mô tả | Bằng chứng |
|---|---|---|---|
| **L8** | **P2 (cao: ảnh hưởng nhật ký duyệt)** | Mỗi lần duyệt, lượt **Facebook luôn bị ghi "Người duyệt sửa: message"** dù không ai sửa. Pinterest không bị. Nguyên nhân có thể là trình duyệt gửi xuống dòng trong textarea dạng `\r\n`, khác bản lưu dùng `\n`, nên phép so sánh `!==` thấy khác **[Suy luận]**. Hệ quả: nhật ký duyệt và dòng thời gian báo sai, mà với ngành bị kiểm soát quảng cáo chặt thì đây là bằng chứng quan trọng. Có thể nội dung lưu cũng bị đổi `\n` thành `\r\n` | Log `review.approved`: lượt #69, #73, #83 đều `"edited":["message"]` dù không sửa. Các lượt Pin (#70, #74, #84) đều `"edited":[]`. Đề xuất: chuẩn hoá `str_replace("\r\n", "\n", …)` trước khi so sánh và lưu |

**Bài thử thêm:**

| Bài | Trạng thái cuối |
|---|---|
| #100 "[TEST] Sắt và tạo máu…" | Cả 2 kênh đã từ chối |
| #101 "[TEST] Viên uống Sắt Folic hộp 30 viên" | Cả 2 kênh đã từ chối. Bài vẫn để **Nháp**, nên không hiện ở cửa hàng |

Trong n8n còn thêm các lần chạy đang chờ tới 07:00 và 09:00 ngày 21/11 (của #100). Tới giờ chúng sẽ nhận `go:false`.

### Vòng 4: chạy lại L8 (20/11, ~10:29), không đăng công khai, không đổi cấu hình

Chuẩn bị: đưa 2 lượt của bài thử #97 về `needs_review` bằng SQL, xoá thông tin duyệt cũ. Nội dung của #97 do AI viết ở chế độ Tự đăng, chưa từng qua form duyệt, nên không có ký tự CR. Cả hai lần duyệt đều dùng **nút thật trên trình duyệt** với giờ đăng 07:00 21/11, rồi Hủy lịch ngay.

| Mã | KQ | Ghi nhận thực tế |
|---|---|---|
| **L8** (duyệt không sửa) | **Đạt** | Nội dung FB có 11 dòng. Log `review.approved` của lượt #79 và #80: `"edited":[]`. Thẻ kênh ghi "Duyệt nguyên văn" ở cả 2 kênh |
| **L8** (duyệt có sửa) | **Đạt** | Thêm 1 dòng vào ô FB → lượt #79 `"edited":["message"]`, ghi chú "Người duyệt sửa: message". Pin vẫn là "Duyệt nguyên văn" |
| L8 (không lưu CR) | Đạt | Sau lần duyệt có sửa, `INSTR(content, CHAR(13)) = 0` ở cả 2 lượt |
| REG-01 | Đạt | Cần duyệt = 2 (#82, #86). Không còn lượt `scheduled`. #97 đã từ chối lại |

Còn để nguyên: (1) CR trong nội dung đã duyệt cũ của #69, #73, #83 (các lượt này đã từ chối); (2) tiền tố thừa trong lý do của L3. Cả hai theo quyết định của PM.

### Vòng 5: đối chiếu luồng thật đầu-cuối của PM, bài #104 (20/11, 10:36–10:37), chỉ đọc

Bài: "Sắc đẹp phụ nữ – Vẻ đẹp bền vững bắt đầu từ sức khỏe bên trong", danh mục Làm đẹp. Lượt FB #87, lượt Pin #88. PM chạy, Tester đối chiếu bằng 4 nguồn:
- Bảng `wp_socialsync_jobs`.
- File log WordPress.
- Trang Trung tâm, chỉ đọc bằng tài khoản `nguoicham`.
- Hai link công khai, mở khi chưa đăng nhập.

**Diễn biến theo log (giờ website):**

| Giờ | Bước |
|---|---|
| 10:36:46.341 | `hook.publish_detected` (auto-draft → publish) |
| 10:36:46.653 | Xếp lịch: chốt 10:36 |
| 10:36:46.803 | n8n nhận bài: HTTP 202, 107 ms, lần chạy #67 |
| 10:37:01 | Chuyển sang `needs_review`. AI viết trong 14,3 giây; 4.722 token vào / 1.624 token ra |
| 10:37:16.830 | `review.approved` ở chế độ đăng ngay (`approve_now`), cả 2 lượt `"edited":[]` |
| 10:37:17.533 | `go.ok` cho lượt #87 |
| 10:37:24.198 | FB `published` |
| 10:37:24.454 | `go.ok` cho lượt #88 |
| 10:37:27.438 | Pin `published` |

| ID | KQ | Ghi nhận thực tế |
|---|---|---|
| REV-01 | Đạt | Duyệt và đăng ngay lúc 10:37:16. FB lên sau 7 giây, Pin sau 11 giây. Cả 2 lượt "Duyệt nguyên văn", `edited_fields = []`, không có CR (L8 đúng trên bài thật) |
| CH-01 | Đạt | Link FB mở khi chưa đăng nhập trả HTTP 200 và chuyển tới `facebook.com/myphamcuatrang/posts/pfbid…`, nên bài **công khai**. Theo callback, bài thuộc loại `"kind":"photo"`. Tester chưa tự xem hình thức bài bằng mắt **[Chưa xác minh trên giao diện FB]** |
| CH-03 | Đạt | Pin công khai: `og:title` là "Pin on Làm đẹp", `pinterestapp:pinboard` = `pinterest.com/196xc/làm-đẹp/`. Link đích là bài #104, có `utm_source=pinterest&utm_medium=social&utm_campaign=socialsync`. `content.board` = "Làm đẹp" (board_map đúng) |
| HUBD-06 | Đạt | Thẻ FB và Pin: "● Đã đăng 10:37 20/11", giờ đăng, giờ đã đăng, người duyệt, lượt n8n #68, nút "Mở bài trên … ↗" đúng link |
| HUBD-11 | Đạt | Dòng thời gian có 7 mục đúng thứ tự: xuất bản → tạo 2 lượt → duyệt nguyên văn (2 mục) → FB đã đăng 10:37:23 → Pin đã đăng 10:37:27 |
| COL-01 | Đạt | Danh sách Bài viết, dòng #104: "FB ● Đã đăng 10:37 20/11 · Pin ● Đã đăng 10:37 20/11" |
| E2E-01 | Đạt một phần | Thời gian từ lúc xuất bản tới khi bài lên FB là 37 giây, trong đó 15 giây là chờ người duyệt. PM chạy bằng trình duyệt ẩn nên **chưa đếm số lần bấm** của người thật |
| CH-04 | Chưa chạy | Luồng này không làm hỏng kênh nào, nên chưa kiểm được việc hai kênh độc lập khi một kênh lỗi |
| ERR-02, S-06, E2E-03 | Chưa chạy | Cần thêm lần đăng thật. PM ghi vào phần giới hạn |

**Phát hiện thêm:**

| Mã | Mức | Mô tả |
|---|---|---|
| L9 | P2 | Dòng log `go.ok` / `go.stop` ghi `post=- ch=-`, thiếu mã bài và kênh. PRD mục 9 yêu cầu mỗi dòng log có `post_id` và kênh. Hiện phải đối chiếu qua `job` |

### Vòng 6: kiểm lại L9 (~10:43), chỉ đọc

| Mã | KQ | Ghi nhận thực tế |
|---|---|---|
| L9 | Đạt | Log mới: `step=go.stop post=104 job=87 ch=facebook` (10:42:48). Dòng cũ lúc 10:37:24 ghi `post=- … ch=-`, tức là trước khi sửa |
