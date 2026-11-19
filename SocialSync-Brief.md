# SocialSync — Project Brief
## Hệ thống đồng bộ nội dung Website WooCommerce → Facebook Fanpage & Pinterest

**Phiên bản:** 0.2 (bản nháp chờ chủ dự án duyệt; lõi xử lý đổi sang n8n tự host, Pinterest nghiệm thu bằng video) · **Ngày:** 2026-11-19 · **PO & thực hiện:** chủ dự án (ứng viên vị trí Quản lý dự án AI) · **Hỗ trợ:** Claude Code · **Tài liệu đi kèm:** `SocialSync-PRD.md`

> **Nhãn:** nội dung chưa kiểm chứng được đánh dấu **[Suy luận]** (hợp lý nhưng chưa có nguồn) hoặc **[Chưa xác minh]** (chưa thử, chưa có nguồn đáng tin). Mọi mục không gắn nhãn là yêu cầu do chủ dự án nêu, hoặc dữ kiện đã kiểm tra ngày 2026-11-19.

---

## 1. Bối cảnh

Đây là "sản phẩm chạy thật" (hạng mục 2.6) của bài test Quản lý dự án AI cho Doppelherz Việt Nam. Đề bài yêu cầu một hệ thống tự động quản lý và đồng bộ nội dung giữa Website, Pinterest và Facebook: đăng một bài lên website thì hệ thống tự tạo và đăng nội dung tương ứng lên hai kênh kia, có lịch đăng và báo cáo kết quả. Hạn nộp là 24 giờ kể từ khi nhận đề.

Website thật `doppelherz.vn` chạy WordPress + WooCommerce nhưng ta không có quyền đăng bài lên đó. Vì vậy bản demo dùng một website WooCommerce riêng đóng vai website của khách hàng, và hai tài khoản mạng xã hội của chủ dự án đóng vai kênh của khách hàng.

## 2. Vấn đề cần giải quyết

Xây dựng một hệ thống mà:

- Người dùng đăng một **bài viết** hoặc một **sản phẩm** trên website WooCommerce.
- Hệ thống tự tạo nội dung phù hợp từng kênh và đăng lên **Facebook Fanpage** và **Pinterest**, không cần thao tác tay nào khác.
- Người dùng xem được kết quả: bài nào đã lên kênh nào, lúc nào, link ở đâu, lỗi gì.

Mức tối thiểu chủ dự án chấp nhận: đăng bài trên website và thấy bài xuất hiện trên Fanpage và Pinterest. Các phần còn lại (AI viết lại, lịch đăng, báo cáo, kiểm tra nội dung) xếp sau, theo thứ tự ở mục 6.

## 3. Định vị so với các cách làm có sẵn

| Cách làm | Điểm yếu | SocialSync khác ở đâu |
|---|---|---|
| Pinterest tự tạo Pin từ RSS | Chậm tới 24 giờ, không viết lại nội dung, không có Facebook | Đăng trong vài phút, có nội dung riêng cho từng kênh |
| Plugin tự đăng bản miễn phí | Không plugin nào đăng được cả Facebook Page lẫn Pinterest ở bản miễn phí | Một luồng cho cả hai kênh |
| Chỉ dùng kịch bản Make.com | Logic nằm trên dịch vụ của hãng khác, tính phí theo lượt khi dùng nhiều, không có nhật ký trong website | Lõi xử lý là n8n tự host: miễn phí, không giới hạn lượt chạy, luồng lưu thành file của ta; nhật ký nằm ngay trong WordPress |
| Plugin catalog chính thức (Meta, Pinterest) | Đồng bộ danh mục sản phẩm để bán hàng, không tạo bài đăng; chờ duyệt 24 giờ trở lên | Tạo bài đăng thấy ngay |

## 4. Mục tiêu & tiêu chí thành công

| Mục tiêu | Đo bằng |
|---|---|
| Đồng bộ chạy thật | Người chấm tự đăng một bài có ảnh, bài xuất hiện công khai trên Fanpage. Pin được tạo tự động trong tài khoản `196xc`, chứng minh bằng video do chủ dự án quay |
| Nhanh | Từ lúc bấm Publish tới lúc bài có trên cả hai kênh ≤ 2 phút ở chế độ đăng ngay (mục tiêu, chưa đo) |
| Không đăng trùng | 0 bài bị đăng hai lần lên cùng một kênh, kể cả khi sửa bài hoặc bấm Publish nhiều lần |
| Truy vết được | 100% lượt đồng bộ có dòng nhật ký với trạng thái, thời gian, link kết quả hoặc nội dung lỗi |
| AI không thêm thông tin | 0 con số trong nội dung AI viết mà không có trong bài gốc |
| Người chấm tự dùng được | Người chấm đăng nhập bằng tài khoản được cấp, đăng bài, xem nhật ký mà không cần hướng dẫn trực tiếp |

## 5. Đối tượng sử dụng

- **Người đăng bài** (trong demo: người chấm điểm; ở Doppelherz: nhân viên marketing): viết bài hoặc tạo sản phẩm trên WordPress như bình thường.
- **Người quản trị** (chủ dự án): cấu hình kết nối, xem nhật ký, bấm đồng bộ lại khi lỗi.
- **Người xem trên mạng xã hội**: thấy bài trên Fanpage và Pin trên Pinterest, bấm link về website.

## 6. Phạm vi

Thứ tự ưu tiên: **M** = bắt buộc cho demo, **S** = nên có vì đề bài nêu, **C** = có nếu còn thời gian. Mức S và C là đề xuất của Claude Code, chờ chủ dự án duyệt.

| # | Hạng mục | Mức |
|---|---|---|
| 1 | Website WordPress + WooCommerce mặc định, truy cập công khai qua `shop.adaptivealgo.uk` | M |
| 1b | n8n tự host trên cùng máy, làm lõi xử lý | M |
| 2 | Tự phát hiện bài viết và sản phẩm mới được xuất bản | M |
| 3 | Đăng lên Fanpage `myphamcuatrang` (ảnh + nội dung + link) | M |
| 4 | Tạo Pin trên Pinterest `196xc` (ảnh + tiêu đề + mô tả + link); nghiệm thu bằng video | M |
| 5 | Chống đăng trùng | M |
| 6 | Log gỡ lỗi chi tiết cho mọi bước | M |
| 7 | Claude viết lại nội dung riêng cho từng kênh | S |
| 8 | Trang nhật ký đồng bộ trong wp-admin, có nút đồng bộ lại | S |
| 9 | Lịch đăng: đăng ngay hoặc trễ N phút sau khi xuất bản | S |
| 10 | Kiểm tra nội dung AI theo luật cứng (từ cấm, con số lạ, giới hạn ký tự) | S |
| 11 | Chế độ chờ duyệt trước khi đăng | C |
| 12 | Loại trừ theo danh mục (không đồng bộ tin nội bộ, thông báo đại lý) | C |
| 13 | Xuất báo cáo CSV | C |

**Ngoài phạm vi bản demo:**

- Đồng bộ khi sửa hoặc xoá bài (bản demo chỉ đồng bộ lần xuất bản đầu tiên).
- Số liệu tương tác (lượt thích, lượt lưu, lượt nhấp).
- Dựng ảnh Pin dọc 2:3 theo mẫu; bản demo dùng ảnh đại diện của bài.
- Trả lời bình luận và tin nhắn.
- Instagram, Zalo, YouTube.
- Giao diện website riêng; dùng giao diện mặc định.
- Tra sổ xác nhận quảng cáo của từng sản phẩm Doppelherz. Phần này nằm trong đề xuất cho khách hàng, không nằm trong demo.

## 7. Ràng buộc bắt buộc

1. **Miễn phí và tự host.** Website chạy trên máy ở nhà của chủ dự án. Chỉ dùng dịch vụ ngoài ở gói miễn phí, trừ Claude API đã có sẵn ngân sách.
2. **Sửa được toàn bộ code.** Logic nằm trong luồng n8n và plugin WordPress do ta viết, cả hai lưu trong thư mục dự án này.
3. **Không đụng FIN05.** Không sửa `app.adaptivealgo.uk`, Caddy, hay bất kỳ container nào của FIN05.
4. **Log chi tiết.** Mọi bước đều ghi log đủ để tìm nguyên nhân gốc mà không phải đoán.
5. **Không lộ khoá bí mật.** Khoá Claude API, địa chỉ webhook và mật khẩu không nằm trong thư mục code và không xuất hiện trong log.
6. **AI chỉ viết lại từ bài gốc.** Không thêm số liệu, công dụng hay cam kết mà bài gốc không có.
7. **Không đăng trùng, không đăng bài trống.** AI lỗi thì dùng mẫu không AI; kênh lỗi thì ghi lỗi và cho đồng bộ lại.
8. **Quy trình làm việc.** Phân tích, thảo luận, chờ chủ dự án đồng ý rồi mới viết code. Chủ dự án tự chạy lệnh cài WordPress và n8n bằng Docker; Claude Code soạn file và hướng dẫn.

## 8. Giả định quan trọng

**Đã kiểm tra ngày 2026-11-19:**

- Máy chủ dự án chạy Docker; tunnel Cloudflare của FIN05 chạy bằng token, tức quản lý từ dashboard Cloudflare.
- App Pinterest mới chỉ có Trial access: Pin tạo ra chỉ người tạo nhìn thấy; xin Standard access cần video demo và không có cam kết thời gian duyệt (nguồn: developers.pinterest.com, diễn đàn Pinterest).
- App Facebook ở Development mode: bài đăng chỉ người có vai trò trên app nhìn thấy (nguồn: developers.facebook.com).
- Make.com có module Pinterest "Create a Pin" và Facebook Pages, do Make duy trì; gói Free có 1.000 credits mỗi tháng (nguồn: make.com).

- Tài khoản Pinterest `196xc` đã chuyển sang Business (chủ dự án báo ngày 2026-11-19).
- Bản n8n miễn phí (Community) không có chia sẻ luồng và không có quyền chỉ xem: chỉ chủ sở hữu truy cập được luồng và các kết nối (nguồn: docs.n8n.io).
- Cấp quyền Trial cho app Pinterest cũng phải chờ duyệt. Pinterest thừa nhận có chậm trễ; người dùng trên diễn đàn báo chờ 1–4 tuần (nguồn: community.pinterest.biz).

**Chưa kiểm tra, phải thử trước khi dựng luồng:**

- [Chưa xác minh] App Meta tự tạo ở Live mode đăng công khai lên Fanpage của chính chủ app mà không cần App Review. Tài liệu bên thứ ba và diễn đàn n8n nói được; tài liệu Meta không nói rõ.
- [Chưa xác minh] Pin tạo qua kết nối Pinterest mặc định của Make.com. Có người dùng báo lỗi 401, và lỗi 403 kéo dài từ 09/2025; nhân viên Make khuyên tự tạo app Pinterest, mà app tự tạo thì quay lại giới hạn Trial.
- [Chưa xác minh] Gói Free của Make.com cho dùng webhook. Chỉ cần nếu dùng Make cho chặng Pinterest.
- [Suy luận] Cloudflare có thể chặn Pinterest và Facebook tải ảnh từ `shop.adaptivealgo.uk`. Nếu bị chặn, kiểm tra Security → Events trên Cloudflare trước khi sửa.
- [Suy luận] Mỗi bài tốn vài nghìn token Claude, rất nhỏ so với ngân sách 90 USD. Sẽ đo và ghi vào nhật ký.

## 9. Rủi ro chính

| Rủi ro | Ảnh hưởng | Giảm thiểu |
|---|---|---|
| Không có đường nào tạo được Pin bằng máy trong 24 giờ (Make lỗi, app Pinterest chưa được duyệt Trial) | Không có video chứng minh nhánh Pinterest | Đăng ký app Pinterest và thử Make ngay từ đầu. Phương án lùi theo thứ tự ở PRD mục 7.5: Pinterest tự đăng từ RSS, rồi xuất gói Pin để đăng tay; nói rõ giới hạn trong bài nộp |
| App Meta tự tạo không đăng công khai được | Người chấm không thấy bài trên Fanpage | Thử bằng cửa sổ ẩn danh ngay khi có token. Phương án lùi: đăng Facebook qua Make |
| n8n mở công khai tại `n8n.adaptivealgo.uk` cho khách đăng nhập (chủ dự án đã chốt) | Người đăng nhập có toàn quyền sửa luồng, dùng token Facebook và khoá Claude, và gửi được yêu cầu vào mạng Docker chung với FIN05 | Tạo tài khoản chủ sở hữu trước khi mở link; dùng mật khẩu riêng và đổi sau khi chấm xong; sao lưu luồng ra `n8n/`; token Facebook chỉ cấp quyền cho Fanpage demo; đặt hạn mức chi tiêu thấp cho khoá Claude |
| Máy ở nhà hoặc tunnel rớt lúc người chấm vào | Demo hỏng | Quay video demo dự phòng; kiểm tra lại trước giờ nộp |
| Người chấm có quyền đăng nhập vào máy ở nhà | Lộ hoặc hỏng hệ thống | Cấp vai trò Shop manager, không cấp Administrator; tắt sửa file trong wp-admin |
| Hết thời gian vì làm phần S, C trước khi M chạy | Không có sản phẩm chạy thật | Làm xong và test hết mức M rồi mới sang S |
| Nội dung AI sai hoặc phản cảm lên Fanpage công khai | Mất điểm | Kiểm tra luật cứng; có mẫu không AI; Fanpage demo không có người theo dõi |

## 10. Cấu trúc thư mục & liên kết

```
test-social/
  Bai_test_Quan_ly_du_an_AI.docx.md   đề bài
  SocialSync-Brief.md                 tài liệu này
  SocialSync-PRD.md                   yêu cầu sản phẩm
  research/                           nghiên cứu Doppelherz (đã có)
  docker-compose.yml, .env.example    cài WordPress (và n8n) bằng Docker
  plugin/social-sync/                 code plugin WordPress (chưa viết)
  n8n/                                file luồng n8n, không chứa khoá bí mật (chưa làm)
  logs/                               file log, nằm ngoài webroot
  docs/                               tài liệu nộp bài (làm sau khi code xong)
```

- Website demo: `https://shop.adaptivealgo.uk` (chưa dựng)
- Fanpage: https://www.facebook.com/myphamcuatrang/
- Pinterest: https://www.pinterest.com/196xc/
- Nghiên cứu nền: `research/HANDOFF.md`, `research/01_phan_tich_de_bai.md`, `research/02_template_3_kenh_va_huong_prototype.md`

## 11. Timeline (24 giờ, tính từ lúc nhận đề)

| Mốc | Việc | Ai |
|---|---|---|
| T+0 → T+1 | Brief và PRD | Claude Code viết, chủ dự án duyệt |
| T+1 → T+3 | Cài WordPress + WooCommerce + n8n, mở `shop.adaptivealgo.uk`; đăng ký app Pinterest; tạo app Meta; thử tạo Pin qua Make | Chủ dự án làm, Claude Code hướng dẫn |
| T+3 → T+7 | Plugin mức M + luồng n8n mức M; test đầu cuối với bài thật | Claude Code viết, chủ dự án test |
| T+7 → T+10 | Mức S: Claude viết lại, nhật ký, lịch đăng, kiểm tra nội dung | Claude Code viết, chủ dự án test |
| T+10 → T+18 | Tài liệu nộp: Câu 1, chẩn đoán, kế hoạch, báo giá, sơ đồ, bộ prompt, lộ trình | Sau khi code xong |
| T+18 → T+21 | Quay video demo, tạo tài khoản người chấm, đóng gói | Chủ dự án |
| T+21 → T+24 | Dự phòng | |
