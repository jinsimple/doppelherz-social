# 2.6 Sản phẩm chạy thật – hướng dẫn dùng thử

Hệ thống đang chạy thật. Anh/chị có thể tự đăng bài trên website demo, duyệt nội dung do AI viết, và xem bài tự lên Facebook Fanpage và Pinterest.

## 1. Địa chỉ

| Thành phần | Địa chỉ |
|---|---|
| Website demo (WordPress + WooCommerce) | https://shop.adaptivealgo.uk |
| Trang quản trị website | https://shop.adaptivealgo.uk/wp-admin |
| n8n (bộ máy xử lý, tự host) | https://n8n.adaptivealgo.uk |
| Facebook Fanpage nhận bài | https://www.facebook.com/myphamcuatrang/ |
| Pinterest nhận Pin | https://www.pinterest.com/196xc/ (board "Sức khoẻ & Dinh dưỡng") |
| Mã nguồn | https://github.com/jinsimple/doppelherz-social |

**Tài khoản dùng thử:** ứng viên gửi kèm bài nộp.
- Quyền là *Quản lý cửa hàng*: đăng bài và sản phẩm, duyệt nội dung, xem Nhật ký, Báo cáo, Chạy thử.
- Không vào được trang Cấu hình hệ thống.

## 2. Thử trong 5 phút

1. **Đăng bài.** Đăng nhập trang quản trị, rồi chọn một trong hai cách:
   - **Bài viết:** *Bài viết → Viết bài mới*. Nhập tiêu đề, vài đoạn nội dung, chọn **Ảnh đại diện** (Pinterest bắt buộc có ảnh).
     - Ở cột phải có bảng **Lịch đăng mạng xã hội**. Để trống thì hệ thống tự chọn giờ.
     - Bấm **Đăng**.
   - **Sản phẩm:** *Sản phẩm → Thêm mới*. Nhập tên, mô tả, giá, ảnh sản phẩm, rồi bấm **Đăng**.
2. **Chờ AI viết.** Sau 10–30 giây, menu **SocialSync → Chờ duyệt** hiện số bài cần duyệt.
3. **Duyệt.** Bấm **Xem và duyệt**. Màn hình cho thấy:
   - Claude phân loại bài là *Kiến thức / Sản phẩm / Tin thương hiệu / Không đồng bộ*, kèm lý do.
   - Nội dung Facebook và Pinterest do Claude viết, sửa được trực tiếp.
   - Giờ đăng do bộ xếp lịch đề xuất, đổi được.

   Rồi bấm một trong ba nút:
   - **Duyệt và đăng ngay**: muốn thấy kết quả liền thì chọn nút này.
   - **Duyệt và đăng theo giờ đã chọn**.
   - **Từ chối**: phải ghi lý do.
4. **Xem kết quả.** Vào **SocialSync → Nhật ký đồng bộ**, tải lại sau 10–30 giây. Lượt chuyển *Đã đăng* kèm link mở bài trên Facebook hoặc Pin trên Pinterest.

Một sản phẩm **đã chờ sẵn** trong *Chờ duyệt*. Anh/chị có thể duyệt ngay mà không cần đăng bài mới.

## 3. Những điều nên thử thêm

| Thử | Kỳ vọng |
|---|---|
| Đăng bài kiểu "Thông báo chương trình chiết khấu cho nhà thuốc, đại lý" | Claude phân loại *Không đồng bộ*: cả hai kênh *Bỏ qua*, có lý do |
| Đăng bài về sự kiện, hoạt động xã hội của công ty | *Tin thương hiệu*: Facebook chờ duyệt, Pinterest bỏ qua |
| Đăng sản phẩm có giá | Dòng "Giá: …" lấy từ WooCommerce. Câu khuyến cáo "Thực phẩm này không phải là thuốc…" là dòng riêng, do hệ thống gắn |
| Đưa "chữa khỏi 100%" vào bài | AI tránh từ cấm; nếu vẫn lọt thì màn hình duyệt hiện cảnh báo đỏ |
| Sửa bài đã đăng rồi bấm Cập nhật | Không tạo lượt mới (chống đăng trùng) |
| Đăng bài không có ảnh | Facebook vẫn đăng; Pinterest *Bỏ qua* vì thiếu ảnh |
| **SocialSync → Chạy thử doppelherz.vn** → Chạy thử | Lấy bài thật mới nhất của doppelherz.vn, Claude phân loại và viết, **không đăng gì**. Ô "Bỏ qua N bài mới nhất" để chọn bài cũ hơn, ví dụ bài kiến thức |
| **SocialSync → Báo cáo** → nút tạo báo cáo | Sau ~30 giây có báo cáo tuần do Claude tóm tắt. Bảng tỉ lệ thành công, chi phí AI, số bài theo ngày |

## 4. Những gì đã kiểm chứng (19/11/2026)

| Kiểm chứng | Kết quả |
|---|---|
| Bài lên Facebook | ~7 giây sau khi duyệt/đăng. Bài công khai, đã xem bằng cửa sổ ẩn danh |
| Pin lên Pinterest | Đúng giờ đặt (thử 1 phút → 62 giây). Pin xem được khi không đăng nhập |
| Duyệt bằng tài khoản người chấm | Từ chối, duyệt kèm sửa tiêu đề Pin, chặn duyệt lần hai: đều đúng |
| Phân loại | Bài kiến thức → E; sản phẩm → P có khuyến cáo; thông báo đại lý → X; tin sự kiện → N; bài "Tin nội bộ" chặn ngay ở WordPress |
| Lịch tự rải | Ba bài đăng gần nhau được xếp 19:24 / 19:54 / 20:24 (cách 30 phút). Khóa xếp lịch sửa lỗi hai bài cùng giờ phát hiện khi thử |
| Chế độ Tự đăng + giờ chọn tay | Pin đăng đúng giờ người viết chọn |
| Chạy thử trên doppelherz.vn | 5 bài thật: 2 chặn theo "Tin nội bộ", 1 → X (danh sách trúng thưởng cho đại lý), 2 → N |
| n8n lỗi giữa chừng | Luồng báo lỗi đánh dấu *Lỗi* trong vòng 1 giây, ghi tên khối lỗi |
| AI không dùng được | Tự dùng nội dung mẫu, ghi lý do (thử với API key sai: HTTP 401) |
| Chi phí AI | 1 lần gọi/bài, đo được $0,027–0,052/bài |
| Đọc tương tác Facebook | Báo cáo tuần đọc reaction, bình luận, chia sẻ của 4 bài đã đăng trong 1 lần gọi gộp, lưu vào từng lượt. Token Trang không hết hạn |

## 5. Giới hạn của bản demo

- **Fanpage và Pinterest là tài khoản thử của ứng viên**, không phải tài khoản Doppelherz. Bài anh/chị duyệt sẽ hiện công khai trên các trang này.
- **Chưa tra sổ xác nhận quảng cáo (XNQC)** cho bài sản phẩm, chưa có lớp AI rà soát tuân thủ thứ hai. Nhóm nhạy cảm (trẻ em, phụ nữ mang thai, nhân viên y tế) mới được gắn cờ cảnh báo, chưa có lớp duyệt pháp chế.
- **Pinterest đi qua Make.com.** App Pinterest API riêng đang chờ duyệt, nên chưa đọc được số liệu Pin.
- **Ảnh Pin dùng thẳng ảnh đại diện của bài**, chưa dựng ảnh dọc 2:3 theo mẫu.
- **Máy chủ đặt tại nhà ứng viên.** Nếu máy tắt hoặc mất mạng thì website và n8n tạm không truy cập được.
- Bảng **Lịch đăng mạng xã hội** trong trình soạn khối đã kiểm tra ở tầng dữ liệu (lưu và đọc giờ qua REST), chưa chụp màn hình trên trình duyệt **[Chưa xác minh trên giao diện]**.

## 6. Xem bên trong hệ thống

- **Sơ đồ:** 3 luồng n8n (chính 28 khối, báo lỗi, báo cáo tuần) ở tài liệu 2.4. Mỗi lượt trong Nhật ký có link sang lần chạy tương ứng trên n8n (cần tài khoản n8n).
- **Mã nguồn:**
  - plugin WordPress: `plugin/social-sync/`
  - luồng n8n: `n8n/workflows/`
  - script nạp luồng: `scripts/`
  - cấu hình Docker: `docker-compose.yml`
- **Log chi tiết:** mỗi bước ghi một dòng có mã yêu cầu chung giữa WordPress và n8n. Nhờ vậy truy được một bài đi qua những bước nào, ai duyệt, mất bao lâu, lỗi ở đâu.
