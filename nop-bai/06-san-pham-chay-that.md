# 2.6 Sản phẩm chạy thật – hướng dẫn dùng thử

Hệ thống đang chạy thật. Anh/chị có thể tự đăng bài trên website demo, duyệt nội dung do AI viết, và xem bài tự lên Facebook Fanpage và Pinterest.

## 1. Địa chỉ

| Thành phần | Địa chỉ |
|---|---|
| Website demo (WordPress + WooCommerce) | https://shop.adaptivealgo.uk |
| Trang quản trị website | https://shop.adaptivealgo.uk/wp-admin |
| n8n (bộ máy xử lý, tự host) | https://n8n.adaptivealgo.uk |
| Facebook Fanpage nhận bài | https://www.facebook.com/myphamcuatrang/ |
| Pinterest nhận Pin | https://www.pinterest.com/196xc/ (board theo danh mục: *Làm đẹp* → board "Làm đẹp", còn lại → "Sức khoẻ & Dinh dưỡng") |
| Mã nguồn | https://github.com/jinsimple/doppelherz-social |

**Tài khoản dùng thử:** ứng viên gửi kèm bài nộp.
- Quyền là *Quản lý cửa hàng*: đăng bài và sản phẩm, duyệt nội dung trong **Trung tâm SocialSync**, xem Báo cáo, Nhật ký chi tiết, Chạy thử.
- Không vào được trang Cấu hình hệ thống.

## 2. Thử trong 5 phút

Mọi việc với mạng xã hội sau khi bấm Đăng bài nằm ở **một trang: menu SocialSync → Trung tâm** (số đỏ cạnh menu là số bài chờ duyệt).

1. **Đăng bài.** Đăng nhập trang quản trị, rồi chọn một trong hai cách:
   - **Bài viết:** *Bài viết → Viết bài mới*. Nhập tiêu đề, vài đoạn nội dung, chọn **Ảnh đại diện** (Pinterest bắt buộc có ảnh).
     - Cột phải có bảng **Mạng xã hội (SocialSync)**: chọn giờ đăng từng kênh, để trống thì hệ thống tự xếp.
     - Bấm **Đăng**. Không cần rời trang: sau 10–30 giây, bảng tự hiện trạng thái từng kênh (*Chờ duyệt*, *Không đăng: thiếu ảnh*…) và nút **Mở trong Trung tâm SocialSync →**.
   - **Sản phẩm:** *Sản phẩm → Thêm mới*. Nhập tên, mô tả, giá, ảnh sản phẩm, rồi bấm **Đăng**.
2. **Duyệt trong Trung tâm.** Trang chia đôi:
   - Bên trái là danh sách bài, mỗi bài có chip trạng thái từng kênh. Tab *Cần duyệt* mở sẵn, bài đến trước nằm trên.
   - Bên phải là bài đang chọn: Claude phân loại *Kiến thức / Sản phẩm / Tin thương hiệu / Không đồng bộ* kèm lý do; nội dung Facebook và Pinterest sửa trực tiếp (có đếm ký tự, gõ cụm từ cấm thì hiện cảnh báo ngay); giờ đăng do bộ xếp lịch đề xuất, đổi được.

   Rồi bấm một trong ba nút:
   - **Duyệt và đăng ngay**: muốn thấy kết quả liền thì chọn nút này.
   - **Duyệt và đăng theo giờ đã chọn**.
   - **Từ chối**: chọn lý do nhanh hoặc tự ghi (nút chỉ bấm được khi đã có lý do).

   Ô *Xong thì mở bài chờ duyệt tiếp theo* được tick sẵn: duyệt xong, trang tự mở bài kế; thông báo có link *Xem lại bài vừa xử lý*.
3. **Xem kết quả ngay tại Trung tâm.** Trang tự cập nhật khi có lượt đang xử lý (trừ lúc anh/chị đang sửa form). Thẻ kênh chuyển *Đã đăng* kèm nút mở bài trên Facebook hoặc Pin trên Pinterest. Danh sách *Bài viết* / *Sản phẩm* cũng có cột **Mạng xã hội** với cùng trạng thái.
4. **Đổi ý sau khi đã duyệt theo giờ.** Thẻ kênh *Đã lên lịch* có ô giờ và ba nút **Lưu giờ mới**, **Đăng ngay**, **Hủy lịch** (lượt trở về Chờ duyệt, giữ nguyên nội dung đã duyệt).

Hai bài **đã chờ sẵn** ở tab *Cần duyệt*: một sản phẩm và một bài viết kiến thức. Anh/chị có thể duyệt ngay mà không cần đăng bài mới.

## 3. Những điều nên thử thêm

| Thử | Kỳ vọng |
|---|---|
| Đăng bài kiểu "Thông báo chương trình chiết khấu cho nhà thuốc, đại lý" | Claude phân loại *Không đồng bộ*: cả hai kênh *Bỏ qua*, có lý do |
| Đăng bài về sự kiện, hoạt động xã hội của công ty | *Tin thương hiệu*: Facebook chờ duyệt, Pinterest bỏ qua |
| Đăng sản phẩm có giá | Dòng "Giá: …" lấy từ WooCommerce. Câu khuyến cáo "Thực phẩm này không phải là thuốc…" là dòng riêng, do hệ thống gắn |
| Đưa "chữa khỏi 100%" vào bài | AI tránh từ cấm; nếu vẫn lọt thì màn hình duyệt hiện cảnh báo đỏ |
| Sửa bài đã đăng rồi bấm Cập nhật | Không tạo lượt mới (chống đăng trùng) |
| Đăng bài không có ảnh | Bảng trong trình soạn và Trung tâm đều báo Pinterest *Không đăng: thiếu ảnh*, kèm cách khắc phục. Facebook vẫn chờ duyệt bình thường. Nếu ảnh bị gỡ sau khi AI viết xong, ô Pinterest tự bỏ chọn và hệ thống không cho duyệt Pin |
| Duyệt theo giờ (đặt xa, ví dụ ngày mai), rồi bấm **Hủy lịch** hoặc **Lưu giờ mới** | Hủy: lượt về *Chờ duyệt*, nội dung đã duyệt giữ nguyên. Đổi giờ: lượt đăng theo giờ mới; lần hẹn cũ trong n8n tự bỏ vì n8n hỏi lại WordPress ngay trước khi đăng |
| Xóa trắng nội dung Facebook rồi bấm Duyệt | Trang báo lỗi ngay tại ô, không gửi đi; phần đã sửa không bị mất |
| Gõ tìm không dấu, ví dụ "suc khoe" | Danh sách lọc ra các bài có "sức khỏe" |
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
| Bài không có ảnh | Lượt Pinterest bị bỏ qua ngay khi AI viết xong, lý do hiện ở danh sách và màn hình duyệt. Thử gỡ ảnh rồi cố duyệt Pin: bị chặn, báo lỗi rõ, lượt vẫn ở Chờ duyệt |
| Trung tâm SocialSync (20/11, trình duyệt Edge thật, tài khoản người chấm) | Viết bài mới, chọn giờ Facebook 09:15 ngay trong bảng của trình soạn → bấm Đăng → 24 giây sau bảng tự hiện *Chờ duyệt* → mở Trung tâm: giờ 09:15 được giữ; lý do nhanh bật nút Từ chối; gõ "chữa khỏi 100%" hiện cảnh báo; từ chối xong tự mở bài kế. Không có lỗi JavaScript |
| Luồng thật đầu-cuối (20/11 10:36–10:37, bài thật "Sắc đẹp phụ nữ…" của doppelherz.vn, danh mục Làm đẹp) | Bấm Đăng → 21 giây sau AI viết xong (Kiến thức) → bấm Duyệt và đăng ngay → n8n hỏi lại WordPress (đồng ý) → Facebook lên sau 7 giây, Pin vào **board Làm đẹp** sau 11 giây. Ảnh từng bước: [08-huong-dan-tung-buoc.md](08-huong-dan-tung-buoc.md) |
| Đổi giờ / hủy lịch chạy thật trên n8n (20/11, tab kiểm thử) | Lần hẹn cũ thức dậy hỏi WordPress: lượt đã dời giờ → "không đăng" (mã lệnh không khớp); lượt đã hủy → "không đăng". Không có gì lên kênh |
| Đổi giờ / hủy lịch sau khi duyệt (20/11, tầng dữ liệu, không đăng gì) | 11 tình huống đúng: sai hoặc thiếu mã lệnh → n8n không được đăng; đúng mã → đăng một lần, hỏi lần hai bị từ chối; hủy khi n8n đã nhận lượt → bị chặn; hủy rồi lần hẹn cũ hỏi → không đăng; Tự đăng đã đổi giờ → không đăng; sai mã bí mật → 401; đổi giờ về quá khứ → báo lỗi |

## 5. Giới hạn của bản demo

- **Fanpage và Pinterest là tài khoản thử của ứng viên**, không phải tài khoản Doppelherz. Bài anh/chị duyệt sẽ hiện công khai trên các trang này.
- **Chưa tra sổ xác nhận quảng cáo (XNQC)** cho bài sản phẩm, chưa có lớp AI rà soát tuân thủ thứ hai. Nhóm nhạy cảm (trẻ em, phụ nữ mang thai, nhân viên y tế) mới được gắn cờ cảnh báo, chưa có lớp duyệt pháp lý (người do khách chỉ định).
- **Pinterest đi qua Make.com.** App Pinterest API riêng đang chờ duyệt, nên chưa đọc được số liệu Pin.
- **Ảnh Pin dùng thẳng ảnh đại diện của bài**, chưa dựng ảnh dọc 2:3 theo mẫu.
- **Máy chủ đặt tại nhà ứng viên.** Nếu máy tắt hoặc mất mạng thì website và n8n tạm không truy cập được.
- Trung tâm đọc tối đa 2.000 lượt mới nhất (đủ cho demo); bản chính thức cần phân trang ở tầng cơ sở dữ liệu.
- Chưa có lịch tuần/tháng kéo-thả; đổi giờ làm trong thẻ kênh.
- Đổi giờ bằng tay không kiểm giới hạn số bài/ngày (chỉ cảnh báo khi ngoài khung giờ).
- Bấm cùng một nút hai lần thật nhanh: hệ thống chỉ xử lý một lần (đúng), nhưng lần thứ hai có thể không hiện thông báo.
- Chưa thử trên kênh thật: kênh từ chối giữa chừng rồi bấm "Đăng lại nội dung đã duyệt", đổi giờ rồi để lượt đăng thật theo giờ mới, và đo trọn luồng bằng người bấm tay. Các trường hợp này đã kiểm ở tầng dữ liệu, chưa kiểm đầu-cuối.
- Chưa kiểm với tài khoản Biên tập viên / Tác giả (chỉ kiểm Quản trị và Quản lý cửa hàng).

## 6. Xem bên trong hệ thống

- **Sơ đồ:** 3 luồng n8n (chính 33 khối, báo lỗi, báo cáo tuần) ở tài liệu 2.4. Mỗi thẻ kênh trong Trung tâm có link sang lần chạy tương ứng trên n8n (cần tài khoản n8n).
- **Mã nguồn:**
  - plugin WordPress: `plugin/social-sync/`
  - luồng n8n: `n8n/workflows/`
  - script nạp luồng: `scripts/`
  - cấu hình Docker: `docker-compose.yml`
- **Log chi tiết:** mỗi bước ghi một dòng có mã yêu cầu chung giữa WordPress và n8n. Nhờ vậy truy được một bài đi qua những bước nào, ai duyệt, mất bao lâu, lỗi ở đâu.
