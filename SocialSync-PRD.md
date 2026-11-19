# SocialSync — Product Requirements Document
## Hệ thống đồng bộ nội dung Website WooCommerce → Facebook Fanpage & Pinterest

**Phiên bản:** 0.2 (bản nháp chờ chủ dự án duyệt) · **Ngày:** 2026-11-19 · Đi kèm với `SocialSync-Brief.md`

> **Thay đổi so với 0.1** (theo quyết định của chủ dự án ngày 2026-11-19):
> 1. Lõi xử lý chuyển từ "plugin + Make.com" sang **n8n tự host** trên máy chủ dự án. Plugin WordPress chỉ còn hai việc: báo cho n8n khi có bài mới, và hiển thị nhật ký.
> 2. Nhánh Pinterest được nghiệm thu bằng **video do chủ dự án tự quay**, không yêu cầu người chấm tự xem được Pin.
>
> **Nhãn:** **[Suy luận]** = hợp lý nhưng chưa có nguồn; **[Chưa xác minh]** = chưa thử, chưa có nguồn đáng tin. Các con số ở mục 10 và 16 là **mục tiêu**, chưa có số đo vì chưa có code.
>
> Mức ưu tiên: **M** = bắt buộc cho demo · **S** = nên có vì đề bài nêu · **C** = có nếu còn thời gian.

---

## 1. Personas

| Persona | Bối cảnh & nhu cầu |
|---|---|
| **Người đăng bài** | Trong demo là người chấm điểm; ở doanh nghiệp là nhân viên marketing. Viết bài hoặc tạo sản phẩm trên WordPress như thường lệ. Không muốn học công cụ mới, không muốn đăng tay lần hai, lần ba. |
| **Người quản trị** | Chủ dự án. Cấu hình kết nối một lần, cần biết bài nào lỗi và vì sao, cần sửa nhanh khi lỗi. |
| **Người xem** | Thấy bài trên Fanpage hoặc Pin trên Pinterest. Cần nội dung đọc được trên đúng kênh đó và link về bài gốc. |

---

## 2. User Stories

Định dạng: *Với vai trò [X], tôi muốn [Y], để [Z]* + tiêu chí chấp nhận.

### Nhóm 1 — Nền tảng

**US-001 — Website đăng được bài (M)**
Với vai trò người đăng bài, tôi muốn đăng nhập WordPress và đăng bài viết hoặc sản phẩm, để có nội dung nguồn cho việc đồng bộ.
- Website mở được qua `https://shop.adaptivealgo.uk`, không lặp redirect, CSS hiển thị đúng.
- Tài khoản người chấm tạo và xuất bản được bài viết và sản phẩm kèm ảnh đại diện.
- Tài khoản người chấm không cài được plugin, không sửa được file, không vào được trang cấu hình SocialSync.
- Ảnh đại diện tải được từ bên ngoài mà không cần đăng nhập.

**US-002 — Cấu hình kết nối (M)**
Với vai trò người quản trị, tôi muốn mỗi loại cấu hình nằm ở đúng một chỗ, để không phải sửa code khi đổi tài khoản.
- Trong WordPress: địa chỉ webhook của n8n, mã bí mật dùng chung, bật/tắt từng kênh, loại nội dung được đồng bộ, số phút trễ của từng kênh.
- Trong n8n: khoá Claude API, token Facebook, kết nối Pinterest, prompt, danh sách từ cấm, giới hạn ký tự.
- Không khoá bí mật nào nằm trong thư mục code.
- Trang cấu hình trong WordPress có nút "Gửi thử", trả về thành công hoặc nội dung lỗi; chỉ Administrator vào được.

### Nhóm 2 — Đồng bộ lõi

**US-003 — Tự phát hiện nội dung mới (M)**
Với vai trò người đăng bài, tôi muốn hệ thống tự nhận biết khi tôi xuất bản, để không phải bấm thêm nút nào.
- Bài viết hoặc sản phẩm chuyển sang trạng thái "Đã xuất bản" lần đầu → tạo một lượt đồng bộ cho mỗi kênh đang bật và báo cho n8n.
- Bản nháp, bài riêng tư, bài đặt mật khẩu → không tạo lượt đồng bộ.
- Bài hẹn giờ của WordPress → tạo lượt đồng bộ vào lúc bài thật sự xuất bản.
- Màn hình soạn bài không bị chậm thấy rõ.

**US-004 — Đăng lên Facebook Fanpage (M)**
Với vai trò người đăng bài, tôi muốn bài của tôi xuất hiện trên Fanpage, để người theo dõi thấy.
- Bài trên Fanpage gồm: nội dung, ảnh đại diện, link về bài gốc.
- Bài hiển thị công khai (kiểm tra bằng cửa sổ ẩn danh).
- Bài gốc không có ảnh → vẫn đăng, ở dạng bài kèm link.
- Nhật ký lưu mã bài và link bài trên Facebook.

**US-005 — Tạo Pin trên Pinterest (M)**
Với vai trò người đăng bài, tôi muốn bài của tôi thành một Pin, để người dùng Pinterest tìm thấy.
- Pin gồm: ảnh đại diện, tiêu đề, mô tả, link về bài gốc, nằm trong board đã cấu hình.
- Pin do hệ thống tạo, không có thao tác tay trên Pinterest.
- **Nghiệm thu bằng video** do chủ dự án quay: đăng bài trên website → Pin xuất hiện trong tài khoản `196xc`. Pin không bắt buộc hiển thị công khai với người ngoài.
- Bài gốc không có ảnh → bỏ qua Pinterest, nhật ký ghi rõ lý do "không có ảnh".
- Nhật ký lưu mã Pin và link Pin, kèm ghi chú Pin đang công khai hay chỉ chủ tài khoản thấy.

**US-006 — Không đăng trùng (M)**
Với vai trò người quản trị, tôi muốn mỗi bài chỉ lên mỗi kênh một lần, để Fanpage không bị lặp bài.
- Sửa rồi cập nhật bài đã xuất bản → không tạo lượt đồng bộ mới.
- Chuyển bài về nháp rồi xuất bản lại → không tạo lượt đồng bộ mới.
- Bấm Publish nhiều lần liên tiếp → chỉ có một lượt cho mỗi kênh.
- Lượt đã thành công → nút "Đồng bộ lại" yêu cầu xác nhận trước khi đăng lần hai.

### Nhóm 3 — Nội dung do AI tạo

**US-007 — Claude viết lại theo từng kênh (S)**
Với vai trò người đăng bài, tôi muốn nội dung trên mỗi kênh được viết theo kiểu của kênh đó, để không phải tự viết ba phiên bản.
- Từ một bài gốc, AI trả về: nội dung Facebook, tiêu đề Pin, mô tả Pin, mô tả ảnh (alt-text).
- Kết quả trả về ở dạng dữ liệu có cấu trúc, đúng giới hạn ký tự của mục 3.
- AI chỉ dùng thông tin trong bài gốc.
- Nhật ký ghi: có dùng AI không, model nào, số token vào và ra, thời gian gọi.

**US-008 — Kiểm tra nội dung AI trước khi đăng (S)**
Với vai trò người quản trị, tôi muốn nội dung AI được kiểm tra bằng luật cố định, để không phụ thuộc vào việc AI có nghe lời hay không.
- Mọi con số trong nội dung AI phải có trong bài gốc.
- Nội dung AI không chứa từ trong danh sách cấm; danh sách sửa được trong n8n mà không cần sửa logic.
- Tiêu đề và mô tả không vượt giới hạn ký tự.
- Không đạt → gọi AI lại một lần. Vẫn không đạt → dùng mẫu không AI và ghi rõ lý do.

**US-009 — Vẫn đăng được khi AI lỗi (S)**
Với vai trò người đăng bài, tôi muốn bài vẫn được đồng bộ khi Claude không phản hồi, để việc đăng bài không phụ thuộc một dịch vụ.
- Claude lỗi, hết hạn mức hoặc quá 30 giây → dùng mẫu không AI: tiêu đề + đoạn tóm tắt + link.
- Nhật ký đánh dấu lượt đó "không dùng AI" kèm nguyên nhân.
- Không có trường hợp nào đăng nội dung trống hoặc nội dung là thông báo lỗi.

### Nhóm 4 — Lịch đăng & báo cáo

**US-010 — Lịch đăng (S)**
Với vai trò người quản trị, tôi muốn chọn đăng ngay hay đăng trễ, để bài không dồn lên mạng xã hội cùng lúc.
- Cấu hình được: đăng ngay, hoặc trễ N phút sau khi xuất bản, riêng cho từng kênh.
- Lượt đang chờ hiển thị giờ dự kiến đăng trong nhật ký.

**US-011 — Nhật ký đồng bộ (S)**
Với vai trò người đăng bài, tôi muốn xem danh sách các lượt đồng bộ ngay trong WordPress, để biết bài của mình đã lên kênh nào mà không cần vào công cụ khác.
- Mỗi dòng: bài gốc, kênh, trạng thái, thời gian, link kết quả, nội dung lỗi nếu có.
- Lọc được theo kênh và trạng thái.
- Bấm vào một dòng → xem nội dung đã đăng.
- Người chấm (vai trò Shop manager) xem được trang này.

**US-012 — Đồng bộ lại khi lỗi (S)**
Với vai trò người quản trị, tôi muốn bấm đồng bộ lại lượt bị lỗi, để không phải tạo lại bài.
- Lượt lỗi có nút "Đồng bộ lại"; bấm xong trạng thái cập nhật trên cùng dòng.
- Lỗi mạng tạm thời → hệ thống tự thử lại tối đa 2 lần, cách nhau 1 phút, trước khi báo lỗi.
- Mỗi lần thử để lại dấu vết trong log.

**US-013 — Báo cáo tổng hợp (S phần đếm, C phần xuất file)**
Với vai trò người quản trị, tôi muốn xem tổng số bài đã đồng bộ theo kênh và trạng thái, để báo cáo kết quả.
- Đầu trang nhật ký hiển thị: số lượt thành công, lỗi, bỏ qua, đang chờ theo từng kênh, lọc theo khoảng ngày.
- Số liệu đếm từ bảng nhật ký, không ước lượng.
- (C) Xuất CSV.

### Nhóm 5 — Kiểm soát (mức C)

**US-014 — Chờ duyệt trước khi đăng (C)**
Với vai trò người quản trị, tôi muốn bật chế độ duyệt, để xem nội dung trước khi nó lên kênh công khai.
- Bật chế độ duyệt → lượt đồng bộ dừng ở "Chờ duyệt", hiển thị nội dung sẽ đăng.
- Có đúng ba hành động: Duyệt, Sửa rồi duyệt, Từ chối.
- Ghi lại ai duyệt, lúc nào.

**US-015 — Loại trừ theo danh mục (C)**
Với vai trò người quản trị, tôi muốn chọn danh mục không đồng bộ, để tin nội bộ và thông báo đại lý không lên Fanpage.
- Chọn được danh sách danh mục loại trừ.
- Bài thuộc danh mục loại trừ → nhật ký ghi "bỏ qua: danh mục".

---

## 3. Ánh xạ nội dung theo kênh

| Trường | Bài viết | Sản phẩm | Facebook | Pinterest |
|---|---|---|---|---|
| Tiêu đề | Tiêu đề bài | Tên sản phẩm | Không có trường riêng; AI đưa ý chính vào câu mở | Tiêu đề Pin, tối đa 100 ký tự |
| Nội dung | Nội dung bài | Mô tả + mô tả ngắn | Nội dung AI viết, mục tiêu 300–600 ký tự, 2–3 hashtag | Mô tả Pin, mục tiêu 150–400 ký tự, không hashtag |
| Giá | — | Giá hiện tại | Có nếu là sản phẩm | Có trong mô tả nếu là sản phẩm |
| Ảnh | Ảnh đại diện | Ảnh sản phẩm chính | Ảnh kèm bài | Ảnh Pin |
| Mô tả ảnh | Alt của ảnh, nếu có | Alt của ảnh, nếu có | — | Alt-text do AI viết |
| Link | Link bài + `utm_source=facebook` hoặc `pinterest` | Link sản phẩm + UTM | Trong nội dung bài | Link đích của Pin |
| Nơi đăng | — | — | Fanpage đã kết nối | Board đã cấu hình |

Tham số UTM đầy đủ: `utm_source=<kênh>&utm_medium=social&utm_campaign=socialsync`.

[Chưa xác minh] Giới hạn ký tự chính thức của Pinterest (tiêu đề, mô tả, alt-text). Các giới hạn nằm trong phần cấu hình của luồng n8n và sẽ chỉnh theo kết quả thử.

Mục tiêu độ dài và quy tắc "không hashtag trên Pinterest" lấy từ khảo sát kênh Pinterest của Doppelherz Đức (`research/02_template_3_kenh_va_huong_prototype.md`, mục 1.3).

---

## 4. Trạng thái của một lượt đồng bộ

Mỗi cặp (bài, kênh) là một lượt đồng bộ, có đúng một trạng thái tại mỗi thời điểm. Trạng thái lưu trong WordPress; n8n báo về mỗi khi trạng thái đổi.

| Trạng thái | Nghĩa | Chuyển sang |
|---|---|---|
| `pending` | WordPress vừa tạo, đã báo hoặc đang báo cho n8n | `generating`, `skipped`, `failed` |
| `generating` | n8n đang tạo nội dung (AI hoặc mẫu) | `scheduled`, `needs_review`, `failed` |
| `needs_review` | Chờ người duyệt (chỉ khi bật chế độ duyệt) | `scheduled`, `rejected` |
| `scheduled` | Có nội dung, chờ tới giờ đăng | `published`, `failed` |
| `published` | Đã đăng, có mã và link | (kết thúc) |
| `failed` | Lỗi sau khi đã thử lại | `pending` khi bấm đồng bộ lại |
| `skipped` | Bỏ qua có lý do (không ảnh, danh mục loại trừ, kênh tắt) | (kết thúc) |
| `rejected` | Người duyệt từ chối | (kết thúc) |

Quy tắc: hai kênh độc lập. Facebook lỗi không chặn Pinterest và ngược lại.

---

## 5. Luồng hoạt động

```
[1] Người đăng bài bấm Publish (bài viết hoặc sản phẩm)
[2] Plugin WordPress bắt sự kiện chuyển trạng thái sang "publish"
      - Đã từng đồng bộ?            -> dừng, ghi log
      - Loại nội dung không bật?    -> dừng, ghi log
[3] Plugin tạo lượt đồng bộ cho từng kênh (pending) và gửi dữ liệu bài tới n8n
      qua mạng nội bộ Docker, kèm mã bí mật
[4] n8n tạo nội dung
      - Gọi Claude với bài gốc -> nhận dữ liệu có cấu trúc
      - Kiểm tra luật cứng (con số, từ cấm, độ dài)
      - Không đạt 2 lần, hoặc Claude lỗi -> dùng mẫu không AI
[5] (Nếu bật chế độ duyệt) dừng ở needs_review cho tới khi có người duyệt
[6] n8n chờ tới giờ đăng theo số phút trễ của từng kênh (đăng ngay = 0)
[7] n8n đăng lên từng kênh, độc lập nhau
      Facebook: gọi Graph API bằng token của Fanpage
      Pinterest: theo phương án đang dùng ở mục 7.5
[8] n8n báo kết quả về WordPress cho từng lượt: published hoặc failed,
      kèm mã, link, nội dung đã đăng, mã lượt chạy của n8n
[9] Người dùng xem trang nhật ký trong wp-admin; lượt lỗi có nút đồng bộ lại
```

**Vì sao tách plugin và n8n:**

- **n8n** là phần mềm tự động hoá mã nguồn mở, chạy thêm một container trên máy chủ dự án. Nó miễn phí, không giới hạn số lượt chạy, và luồng xử lý nhìn thấy được dưới dạng sơ đồ. Đây cũng là công cụ dự kiến đưa vào báo giá cho khách hàng.
- **Plugin** phải có vì WordPress không tự báo ra ngoài khi một bài viết được xuất bản. Plugin còn giữ nhật ký ngay trong WordPress, nơi người đăng bài đang làm việc.

---

## 6. Yêu cầu chức năng — đối chiếu US

| # | Yêu cầu | Nằm ở | Mức | US |
|---|---|---|---|---|
| F1 | Website WordPress + WooCommerce công khai, tài khoản người chấm | WordPress | M | US-001 |
| F2 | n8n tự host, luồng xử lý lưu thành file trong thư mục dự án | n8n | M | US-002 |
| F3 | Trang cấu hình kết nối trong WordPress, nút gửi thử | Plugin | M | US-002 |
| F4 | Bắt sự kiện xuất bản bài viết và sản phẩm, báo cho n8n | Plugin | M | US-003 |
| F5 | Chống đăng trùng | Plugin | M | US-006 |
| F6 | Mẫu nội dung không AI | n8n | M | US-009 |
| F7 | Đăng Fanpage | n8n | M | US-004 |
| F8 | Tạo Pin | n8n | M | US-005 |
| F9 | Nhận kết quả từ n8n, lưu bảng nhật ký | Plugin | M | US-004, US-005 |
| F10 | Log gỡ lỗi chi tiết ở cả hai phía | Plugin + n8n | M | mọi US |
| F11 | Claude tạo nội dung theo kênh | n8n | S | US-007 |
| F12 | Bộ kiểm tra luật cứng | n8n | S | US-008 |
| F13 | Đăng trễ N phút theo kênh | n8n | S | US-010 |
| F14 | Trang nhật ký, lọc, xem chi tiết | Plugin | S | US-011 |
| F15 | Tự thử lại và nút đồng bộ lại | n8n + Plugin | S | US-012 |
| F16 | Số đếm tổng hợp theo kênh và trạng thái | Plugin | S | US-013 |
| F17 | Chế độ chờ duyệt | Plugin + n8n | C | US-014 |
| F18 | Loại trừ theo danh mục | Plugin | C | US-015 |
| F19 | Xuất CSV | Plugin | C | US-013 |

Ở mức M, nội dung dùng mẫu không AI (F6). Claude (F11) được bật ở mức S, sau khi luồng đăng đã chạy thông.

---

## 7. Đặc tả thành phần

### 7.1 Plugin WordPress `social-sync` (mỏng)

Vị trí code: `plugin/social-sync/` trong thư mục dự án, đã nối sẵn vào container WordPress qua `docker-compose.yml`.

| Khối | Nhiệm vụ |
|---|---|
| Bắt sự kiện | Nghe chuyển trạng thái bài viết và sản phẩm; quyết định có tạo lượt đồng bộ không |
| Gửi đi | Gửi dữ liệu bài tới webhook của n8n; thử lại khi n8n không phản hồi |
| Nhận về | Một địa chỉ REST riêng nhận kết quả từ n8n; từ chối yêu cầu không có mã bí mật đúng |
| Nhật ký | Bảng dữ liệu + trang trong wp-admin + số đếm tổng hợp + nút đồng bộ lại |
| Cấu hình | Trang cài đặt, nút gửi thử |
| Log | Ghi file log chi tiết vào thư mục `logs/` |

Plugin không gọi Claude, Facebook hay Pinterest.

### 7.2 Dữ liệu WordPress gửi sang n8n (một lần gửi cho mỗi bài)

| Trường | Nội dung |
|---|---|
| `post_id`, `post_type`, `source_url` | Thông tin bài gốc |
| `title`, `content_text`, `excerpt` | Tiêu đề, nội dung đã bỏ HTML, đoạn tóm tắt |
| `price` | Giá, nếu là sản phẩm |
| `image_url`, `image_alt` | Ảnh đại diện và alt gốc; rỗng nếu không có ảnh |
| `categories` | Danh mục của bài |
| `jobs` | Danh sách lượt đồng bộ: mỗi kênh một `job_id` và số phút trễ |
| `callback_url` | Địa chỉ n8n báo kết quả về |

Mã bí mật dùng chung gửi trong header. n8n từ chối yêu cầu không có mã đúng.

### 7.3 Dữ liệu n8n báo về WordPress (mỗi lần đổi trạng thái của một lượt)

| Trường | Nội dung |
|---|---|
| `job_id` | Mã lượt đồng bộ |
| `status` | Trạng thái mới theo mục 4 |
| `content` | Nội dung sẽ đăng hoặc đã đăng |
| `content_source` | `ai` hoặc `template`, kèm lý do nếu là `template` |
| `model`, `tokens_in`, `tokens_out`, `ai_ms` | Thông tin lượt gọi Claude |
| `scheduled_at` | Giờ dự kiến đăng |
| `remote_id`, `remote_url`, `visibility` | Mã, link của bài hoặc Pin; công khai hay chỉ chủ tài khoản thấy |
| `error` | Nội dung lỗi nguyên văn từ nền tảng |
| `execution_id` | Mã lượt chạy của n8n, để đối chiếu log hai phía |

### 7.4 Luồng n8n

Một luồng chính, lưu thành file trong `n8n/` (không chứa khoá bí mật):

```
Webhook nhận bài -> kiểm tra mã bí mật -> khối Cấu hình (prompt, từ cấm, giới hạn ký tự)
  -> Tạo nội dung: Claude -> Kiểm tra luật -> (không đạt) Claude lần 2 -> (không đạt) Mẫu
  -> báo về WordPress: scheduled
  -> tách theo kênh
       Facebook:  chờ N phút -> đăng -> báo về WordPress
       Pinterest: không có ảnh? -> báo skipped
                  có ảnh -> chờ N phút -> tạo Pin -> báo về WordPress
  -> nhánh lỗi chung: báo về WordPress failed kèm nội dung lỗi
```

n8n lưu lại đầu vào và đầu ra của từng khối cho mọi lượt chạy, kể cả lượt thành công.

### 7.5 Kênh Facebook và Pinterest

**Facebook:** n8n gọi Graph API bằng token của Fanpage, lấy từ một app Meta do chủ dự án tạo và chuyển sang Live mode. [Chưa xác minh] App tự tạo ở Live mode đăng công khai lên Fanpage của chính chủ app mà không cần App Review; tài liệu bên thứ ba nói được, tài liệu Meta không nói rõ. Phải thử bằng cửa sổ ẩn danh.

**Pinterest:** thử theo thứ tự, dùng phương án đầu tiên chạy được. Luồng n8n chỉ khác nhau ở một khối.

| Thứ tự | Phương án | Kết quả nếu chạy | Điều chưa chắc |
|---|---|---|---|
| 1 | n8n gọi Make.com, Make dùng kết nối Pinterest mặc định | Pin có thể công khai | Người dùng Make báo lỗi 401 và 403 từ 09/2025; phải thử |
| 2 | n8n gọi thẳng Pinterest API bằng app tự tạo | Pin chỉ chủ tài khoản thấy; đủ để quay video | Cấp quyền Trial cũng phải chờ duyệt; diễn đàn Pinterest ghi nhận 1–4 tuần |
| 3 | Pinterest tự tạo Pin từ RSS của website | Pin công khai, chậm tới 24 giờ | Không đi qua n8n nên không có nội dung AI và không có kết quả trong nhật ký |
| 4 | n8n xuất "gói Pin" (ảnh, tiêu đề, mô tả, link) để đăng tay | Không tự động ở bước cuối | Chỉ dùng khi cả ba phương án trên đều không được |

Việc cần làm ngay vì thời gian chờ nằm ngoài tầm kiểm soát: đăng ký app trên trang developer của Pinterest, và thử phương án 1.

### 7.6 Hợp đồng đầu ra của Claude

Claude nhận: loại nội dung, tiêu đề, nội dung gốc đã bỏ HTML, giá (nếu là sản phẩm). Claude trả về đúng bốn trường:

| Trường | Ràng buộc |
|---|---|
| `facebook_message` | 300–600 ký tự; câu mở nêu chủ đề; 2–3 ý từ bài gốc; lời mời đọc; 2–3 hashtag; không chứa link (n8n tự gắn) |
| `pin_title` | Tối đa 100 ký tự |
| `pin_description` | 150–400 ký tự; kết bằng lời mời đọc; không hashtag |
| `pin_alt_text` | Mô tả ảnh dựa trên tiêu đề và alt gốc; không khẳng định chi tiết hình ảnh mà AI không nhìn thấy |

Ràng buộc chung trong prompt: chỉ dùng thông tin trong bài gốc; không thêm số liệu, công dụng, cam kết; không gán tình trạng sức khoẻ cho người đọc; viết tiếng Việt.

Model đặt trong khối Cấu hình của n8n, chốt khi dựng luồng. Bộ prompt đầy đủ là hạng mục nộp 2.5 của đề bài, nằm trong luồng n8n và trích sang `docs/`.

---

## 8. Mô hình dữ liệu

**Bảng lượt đồng bộ** trong cơ sở dữ liệu WordPress (một dòng cho mỗi cặp bài và kênh):

| Cột | Ý nghĩa |
|---|---|
| `id` | Mã lượt |
| `post_id`, `post_type` | Bài gốc |
| `channel` | `facebook` hoặc `pinterest` |
| `status` | Một trong các trạng thái ở mục 4 |
| `scheduled_at`, `published_at` | Giờ dự kiến, giờ đăng thật |
| `attempts` | Số lần đã gửi sang n8n |
| `content_source` | `ai` hoặc `template`, kèm lý do nếu là `template` |
| `model`, `tokens_in`, `tokens_out`, `ai_ms` | Thông tin lượt gọi Claude |
| `content` | Nội dung đã đăng |
| `remote_id`, `remote_url`, `visibility` | Kết quả từ kênh |
| `error` | Nội dung lỗi gần nhất |
| `execution_id` | Mã lượt chạy của n8n |
| `created_at`, `updated_at` | Dấu thời gian |

Ràng buộc duy nhất trên (`post_id`, `channel`) để chống đăng trùng ở tầng dữ liệu.

**Cấu hình WordPress:** lưu trong bảng tuỳ chọn của WordPress.

**Cấu hình và khoá trong n8n:** khoá Claude, token Facebook, kết nối Pinterest lưu trong kho credential của n8n (được mã hoá). Prompt, danh sách từ cấm, giới hạn ký tự nằm trong khối Cấu hình của luồng.

**Lịch sử chạy của n8n:** n8n tự lưu dữ liệu từng khối của mỗi lượt chạy.

---

## 9. Log gỡ lỗi

Hai nguồn log, nối với nhau bằng `job_id` và `execution_id`.

**Phía WordPress** — file log trong `logs/`, mỗi dòng có: thời gian tới mili giây, mức độ, `job_id`, `post_id`, kênh, tên bước, dữ liệu kèm theo.

| Bước | Ghi gì |
|---|---|
| Bắt sự kiện | Trạng thái cũ và mới, loại nội dung, quyết định tạo hay không tạo lượt và lý do |
| Gửi sang n8n | Địa chỉ đã che, dữ liệu gửi, thời gian, mã trạng thái, phản hồi; lần thử thứ mấy |
| Nhận từ n8n | Dữ liệu nhận, kết quả kiểm tra mã bí mật, trạng thái cũ → mới |
| Đồng bộ lại | Ai bấm, lượt nào |
| Yêu cầu vào website | Header Cloudflare và cách nhận diện HTTPS, bật được khi cần chẩn đoán |

**Phía n8n** — lịch sử chạy, xem được đầu vào và đầu ra của từng khối:

| Bước | Thấy gì |
|---|---|
| Gọi Claude | Model, đầu vào, thời gian, mã trạng thái, số token, nội dung lỗi |
| Kiểm tra | Từng luật đạt hay không, giá trị vi phạm |
| Chờ | Giờ bắt đầu chờ, giờ đăng dự kiến |
| Đăng kênh | Dữ liệu gửi, mã trạng thái, phản hồi nguyên văn của nền tảng |
| Báo về WordPress | Dữ liệu gửi, mã trạng thái |

Quy tắc: file log không ghi khoá API, mã bí mật hay token. File log nằm ngoài webroot, không mở được qua trình duyệt.

---

## 10. Yêu cầu phi chức năng (mục tiêu, chưa đo)

| Yêu cầu | Ngưỡng |
|---|---|
| Thời gian từ Publish tới khi có bài trên Facebook, chế độ đăng ngay | ≤ 2 phút |
| Màn hình soạn bài chậm thêm khi bấm Publish | ≤ 1 giây |
| Thời gian chờ Claude | ≤ 30 giây, quá thì dùng mẫu |
| Thời gian chờ mỗi nền tảng | ≤ 60 giây, quá thì tính là lỗi và thử lại |
| Số lần tự thử lại | 2 |
| Bài đăng trùng | 0 |
| Lượt đồng bộ không có log | 0 |
| Khoá bí mật trong thư mục code hoặc file log | 0 |

---

## 11. Xử lý lỗi & tình huống biên

| Tình huống | Xử lý |
|---|---|
| Bài không có ảnh đại diện | Facebook: đăng dạng link. Pinterest: `skipped`, lý do "không có ảnh" |
| Claude lỗi, quá giờ, hết hạn mức | Dùng mẫu không AI, ghi lý do |
| Nội dung AI không qua kiểm tra | Gọi lại một lần; vẫn không đạt thì dùng mẫu |
| n8n không chạy hoặc không phản hồi | Plugin thử lại 2 lần, rồi `failed` với lý do "không kết nối được n8n" |
| Nền tảng trả lỗi mạng tạm thời | n8n tự thử lại 2 lần, rồi `failed` |
| Nền tảng từ chối (hết quyền, sai board, ảnh không tải được) | `failed` ngay, không thử lại, ghi nguyên văn lỗi |
| n8n đăng xong nhưng không báo về được WordPress | Lượt đứng ở `scheduled` quá 15 phút so với giờ dự kiến → nhật ký cảnh báo "chưa có kết quả, kiểm tra kênh trước khi đồng bộ lại" |
| Sửa bài đã đồng bộ | Không làm gì, ghi log |
| Xoá bài đã đồng bộ | Không xoá bài trên kênh; ngoài phạm vi |
| Nhiều bài xuất bản cùng lúc | Mỗi bài một lượt chạy n8n riêng |
| Cấu hình thiếu (chưa nhập webhook) | Không tạo lượt đồng bộ, hiện cảnh báo trong wp-admin |
| Website không truy cập được từ ngoài | Nền tảng không tải được ảnh → `failed` với lỗi từ nền tảng; kiểm tra tunnel và Cloudflare Security Events |

---

## 12. Tiêu chí nghiệm thu cho demo

**Mức M — phải đạt hết trước khi làm mức S:**

- [ ] Người chấm đăng nhập `shop.adaptivealgo.uk` bằng tài khoản được cấp.
- [ ] Đăng một bài viết có ảnh → bài xuất hiện công khai trên Fanpage, kiểm tra bằng cửa sổ ẩn danh.
- [ ] Cùng bài đó → một Pin được tạo trong tài khoản `196xc`, có video quay lại toàn bộ quá trình.
- [ ] Đăng một sản phẩm có ảnh → kết quả tương tự.
- [ ] Sửa và cập nhật bài vừa đăng → không có bài thứ hai trên kênh nào.
- [ ] Đăng bài không có ảnh → Facebook có bài, Pinterest ghi "bỏ qua: không có ảnh".
- [ ] Log hai phía có đủ các bước của mục 9 cho mỗi lượt trên.

**Mức S:**

- [ ] Nội dung trên Facebook và Pinterest khác nhau và khác bài gốc, đúng giới hạn ký tự.
- [ ] Tắt khoá Claude → bài vẫn đăng bằng mẫu, nhật ký ghi "không dùng AI".
- [ ] Dừng n8n → lượt đồng bộ `failed` có lý do; bật lại rồi bấm "Đồng bộ lại" → `published`.
- [ ] Đặt trễ 5 phút → nhật ký hiển thị giờ dự kiến; bài lên đúng sau khoảng đó.
- [ ] Trang nhật ký hiển thị đúng số lượt theo kênh và trạng thái.

---

## 13. Ngoài phạm vi

Xem Brief mục 6. Nhấn mạnh: không đồng bộ sửa/xoá, không số liệu tương tác, không dựng ảnh Pin dọc, không tra sổ xác nhận quảng cáo, không giao diện website riêng.

---

## 14. Câu hỏi mở

| # | Câu hỏi | Ai trả lời | Chặn việc gì |
|---|---|---|---|
| 1 | Phương án Pinterest nào chạy được (mục 7.5)? | Thử thực tế | Khối tạo Pin trong n8n |
| 2 | App Meta tự tạo ở Live mode có đăng công khai lên Fanpage không? | Thử thực tế | Nhánh Facebook |
| 3 | **Đã chốt 2026-11-19:** mở `https://n8n.adaptivealgo.uk` cho khách vào xem. Bản n8n miễn phí không có quyền chỉ xem, nên khách đăng nhập bằng tài khoản chủ sở hữu và có toàn quyền. Còn mở: có đặt thêm Cloudflare Access trước link không | Chủ dự án | Cách cấp và thu hồi quyền vào n8n |
| 4 | Chế độ mặc định khi demo: đăng ngay hay chờ duyệt? Đề xuất: đăng ngay | Chủ dự án | US-014 |
| 5 | Mức S và C ở Brief mục 6 có đúng thứ tự chủ dự án muốn không? | Chủ dự án | Thứ tự làm |
| 6 | Nội dung demo viết về chủ đề gì (sản phẩm Doppelherz hay nội dung trung tính)? | Chủ dự án | Bài mẫu, danh sách từ cấm |

---

# PHẦN AI-NATIVE

## 15. Unit of AI Work

Thu hẹp việc của AI tới mức viết được tiêu chí Đạt/Không đạt:

| Trục | SocialSync thu hẹp thành |
|---|---|
| Theo việc | Viết lại một bài đã có thành nội dung cho hai kênh. Không phải: viết bài mới, chọn ảnh, quyết định có đăng hay không |
| Theo đầu vào | Một bài viết hoặc một sản phẩm tiếng Việt, đã xuất bản, có tiêu đề và nội dung |
| Theo mức tự trị | AI soạn nội dung; luật cứng quyết định nội dung có được dùng không; AI không tự đăng |

**Đơn vị công việc:** *một bài gốc → bốn trường nội dung đúng định dạng và giới hạn ký tự, không chứa con số hay từ cấm ngoài bài gốc, trong 30 giây.*

## 16. Tiêu chí đánh giá

Kiểm tra tự động, áp dụng cho mọi lượt:

| Tiêu chí | Ngưỡng đạt |
|---|---|
| Đủ bốn trường, đúng định dạng | 100% |
| Đúng giới hạn ký tự | 100% |
| Con số không có trong bài gốc | 0 |
| Từ trong danh sách cấm | 0 |
| Thời gian phản hồi | ≤ 30 giây |

Kiểm tra bằng người, trên bộ bài mẫu ở mục 17:

| Tiêu chí | Cách chấm |
|---|---|
| Đúng ý bài gốc | Đạt nếu không có ý nào bài gốc không nói |
| Đọc tự nhiên trên kênh đó | Đạt nếu người chấm sẵn sàng đăng mà không sửa |
| Hai kênh khác nhau thật | Đạt nếu nội dung Pinterest không phải bản cắt ngắn của Facebook |

Giới hạn đã biết: luật cứng chỉ bắt con số và từ trong danh sách. Một câu khẳng định sai mà không chứa số và không chứa từ cấm sẽ lọt. Vì vậy tiêu chí "đúng ý bài gốc" phải do người chấm, và chế độ chờ duyệt (US-014) là khuyến nghị bắt buộc khi triển khai cho doanh nghiệp thật.

## 17. Bộ bài mẫu để thử

| Bộ | Nguồn | Số lượng | Dùng để |
|---|---|---|---|
| Bài kiến thức | 40 bài mới nhất danh mục "Kiến thức sức khỏe" của doppelherz.vn (`research/doppelherz_cau_truc_40_bai.csv`) | Chọn 10 | Thử độ dài, giọng văn, luật con số |
| Sản phẩm | 36 sản phẩm có công dụng đã xác nhận (`research/doppelherz_san_pham_xnqc.csv`) | Chọn 5 | Thử việc AI không thêm công dụng |
| Tình huống biên | Tự soạn: bài rất ngắn, bài không ảnh, bài nhiều số liệu, bài có từ cấm | 5 | Thử luật cứng và mẫu không AI |

Bộ này dùng để thử nội dung AI tạo ra, đọc từ dữ liệu công khai đã thu thập. Việc có đăng nội dung Doppelherz lên Fanpage demo hay không là câu hỏi mở số 6.

## 18. Chế độ hỏng của model

| Chế độ hỏng | Hành vi đã thiết kế |
|---|---|
| Claude không phản hồi, lỗi máy chủ, hết hạn mức | Dùng mẫu không AI; mẫu là văn bản viết sẵn, không do model sinh |
| Trả về sai định dạng | Tính là không đạt; gọi lại một lần |
| Thêm con số hoặc từ cấm | Luật cứng chặn; gọi lại một lần; rồi dùng mẫu |
| Vượt giới hạn ký tự | Gọi lại một lần; rồi dùng mẫu. Không tự cắt chữ giữa câu |
| Từ chối viết | Tính là không đạt; dùng mẫu |

**"Hỏng êm" của SocialSync, một câu:** *AI hỏng thì bài vẫn lên bằng mẫu và nhật ký nói rõ là không dùng AI; hệ thống không bao giờ đăng nội dung trống, nội dung lỗi, hoặc đăng trùng.*

## 19. Ràng buộc kỹ thuật

```
website       WordPress + WooCommerce trong Docker; plugin tự viết (PHP)
lõi xử lý     n8n bản Community, tự host trong Docker, cùng file docker-compose.yml
              bản miễn phí chỉ có một chủ sở hữu, không có quyền chỉ xem
AI            Claude API; model cấu hình được; khoá nằm trong kho credential của n8n
Facebook      Graph API qua app Meta tự tạo, Live mode
Pinterest     theo thứ tự phương án ở mục 7.5
truy cập      Cloudflare Tunnel sẵn có -> shop.adaptivealgo.uk; không sửa FIN05
              n8n mở tại n8n.adaptivealgo.uk cho khách xem (quyết định của chủ dự án)
              WordPress gọi n8n qua mạng nội bộ Docker (http://n8n:5678)
log           file trong logs/ (ngoài webroot) + bảng nhật ký + lịch sử chạy của n8n
```
