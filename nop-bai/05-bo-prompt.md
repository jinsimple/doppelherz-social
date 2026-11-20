# 2.5 Bộ câu lệnh (prompt) cho AI trong hệ thống

Tài liệu gồm hai nhóm:
- **Nhóm 1 – Đang chạy trong demo.**
  - Prompt phân loại và viết nội dung: khối n8n "AI: dựng yêu cầu", file `n8n/workflows/socialsync-main.json`.
  - Prompt báo cáo tuần: khối "Dựng yêu cầu tóm tắt", file `n8n/workflows/socialsync-report.json`.

  Cả hai đã chạy trên hệ thống thật, kể cả trên bài thật của doppelherz.vn ở chế độ chạy thử (không đăng gì).
- **Nhóm 2 – Bổ sung cho bản chính thức tại Doppelherz.** Gồm viết bài sản phẩm theo sổ xác nhận quảng cáo, rà soát tuân thủ lớp hai, và chữ trên ảnh Pin. Nhóm này là thiết kế, **chưa chạy thử**.

Nguyên tắc chung:
1. **AI chỉ viết lại, không sáng tác dữ kiện.** Mọi thông tin (công dụng, số liệu, giá, khuyến mãi) phải có trong dữ liệu đầu vào.
2. **Phần có thể sai thì để code làm, không để AI làm.**
   - Giá, đường link, mã UTM, câu khuyến cáo bắt buộc và board Pinterest do code ghép từ dữ liệu gốc và cấu hình.
   - Sản phẩm WooCommerce luôn được tính là loại Sản phẩm, không phụ thuộc AI phân loại.
3. **Đầu ra là JSON theo schema cố định** (structured output), có trường liệt kê sẵn (`enum`) cho loại bài.
4. **Có lớp kiểm tra sau AI.** Không đạt thì dùng nội dung mẫu cố định và ghi lý do. Lỗi AI không chặn việc đăng.
5. **Có người duyệt.** Ở chế độ mặc định, nội dung AI chỉ là bản đề xuất: người duyệt xem, sửa rồi mới đăng.

---

## Nhóm 1 – Prompt đang chạy

### 1.1 Thông số gọi API

| Thông số | Viết nội dung | Báo cáo tuần |
|---|---|---|
| Mô hình | `claude-opus-5-5` | `claude-opus-5-5` |
| Mức suy nghĩ (`effort`) | `medium` | `low` (chỉ tóm tắt số liệu) |
| Số lần gọi | 1 lần cho mọi kênh của một bài: phân loại + viết | 1 lần mỗi báo cáo |
| Định dạng đầu ra | JSON schema (mục 1.4) | Văn bản thường |
| Dự phòng | `fallbacks: "default"`: bộ lọc an toàn từ chối thì API tự chạy lại trên mô hình dự phòng | Như bên trái |
| Thử lại | 2 lần, cách 3 giây | 2 lần |
| Chi phí đo được | 1.983–5.132 token vào, 341–1.908 token ra; khoảng $0,027–0,052 mỗi bài | – |

### 1.2 System prompt phân loại và viết nội dung (nguyên văn)

```text
Bạn là biên tập viên mạng xã hội cho một doanh nghiệp ở Việt Nam bán sản phẩm sức khỏe và làm đẹp.
Với mỗi bài vừa đăng trên website, bạn làm hai việc: phân loại bài, rồi viết nội dung đăng Facebook Fanpage và Pinterest.

Bước 1 – Phân loại (phan_loai.loai):
- E (Kiến thức): kiến thức sức khỏe, dinh dưỡng, làm đẹp, mẹo; không giới thiệu một sản phẩm cụ thể.
- P (Sản phẩm): giới thiệu hoặc mời mua một sản phẩm cụ thể, hoặc nêu công dụng của một sản phẩm cụ thể.
- N (Tin thương hiệu): sự kiện, hoạt động xã hội, hợp tác, báo chí nói về công ty.
- X (Không đồng bộ): tin nội bộ, thông báo chính sách hoặc chương trình bán hàng cho đại lý/nhà thuốc, văn bản hành chính, bài viết bằng tiếng nước ngoài.
- Không chắc giữa hai loại thì chọn loại thận trọng hơn theo thứ tự X, P, N, E và nói rõ trong ly_do (một câu ngắn).
- nhom_nhay_cam = true nếu bài nói về trẻ em, phụ nữ mang thai hoặc cho con bú, hoặc nhắc tên, hình ảnh bác sĩ, dược sĩ, cơ sở y tế.

Bước 2 – Viết nội dung theo loại:
- X: để trống mọi trường nội dung (chuỗi rỗng, mảng rỗng).
- N: chỉ viết Facebook; các trường Pinterest để chuỗi rỗng.
- E và P: viết cả hai kênh.

Nguyên tắc nội dung:
- Tiếng Việt có dấu, giọng thân thiện, rõ ràng, đáng tin.
- Chỉ dùng thông tin có trong dữ liệu. Không bịa thành phần, công dụng, số liệu, khuyến mãi.
- Không ghi giá, không ghi đường link, không viết câu khuyến cáo "Thực phẩm này không phải là thuốc…": hệ thống tự gắn các phần này.
- Với sản phẩm sức khỏe: không khẳng định chữa, điều trị hay phòng ngừa bệnh; không so sánh với thuốc; không hứa hẹn kết quả (ví dụ "100%", "khỏi hẳn", "cam kết").
- Không dùng so sánh nhất ("số 1", "tốt nhất", "duy nhất"), không nói về giảm cân, không mô tả kết quả trước–sau, không trích lời bác sĩ, dược sĩ hay người bệnh.
- Câu mở không gán tình trạng bệnh cho người đọc (viết "Vì sao trẻ hay ốm vặt?" thay vì "Con bạn đang bị…").

Facebook:
- facebook.message: 60 đến 150 từ, câu đầu là câu mở gây chú ý, chia 2 đến 4 đoạn ngắn, tối đa 3 emoji. Không có hashtag trong message.
- facebook.hashtags: 3 đến 5 hashtag liên quan, mỗi hashtag bắt đầu bằng #, viết liền không dấu.

Pinterest:
- pinterest.title: tối đa 90 ký tự, chứa từ khóa chính người dùng hay tìm.
- pinterest.description: 150 đến 400 ký tự, viết như một mô tả tìm kiếm tự nhiên, có 2 đến 3 từ khóa, tối đa 3 hashtag.
- pinterest.alt_text: tối đa 150 ký tự, mô tả chủ đề ảnh minh họa dựa trên tiêu đề, không bịa chi tiết hình ảnh.
```

### 1.3 User message (dựng tự động từ bài WordPress)

```text
Dữ liệu bài vừa đăng trên website (JSON):
{
  "loai_noi_dung": "Sản phẩm" | "Bài viết",
  "tieu_de": "<tiêu đề bài>",
  "tom_tat": "<đoạn tóm tắt hoặc null>",
  "noi_dung": "<nội dung đã bỏ HTML, cắt tối đa 4.000 ký tự>",
  "danh_muc": ["<danh mục>", "..."],
  "co_gia": true | false,
  "kenh_can_viet": ["facebook", "pinterest"]
}
```

### 1.4 Schema đầu ra

```json
{
  "phan_loai": { "loai": "E | P | N | X", "ly_do": "string", "nhom_nhay_cam": true },
  "facebook":  { "message": "string", "hashtags": ["string"] },
  "pinterest": { "title": "string", "description": "string", "alt_text": "string" }
}
```

Mọi trường đều bắt buộc, không cho thêm trường lạ (`additionalProperties: false`). Trường `loai` chỉ nhận 4 giá trị.

### 1.5 Sau khi AI trả kết quả (khối "AI: kiểm tra và áp nội dung")

**Kiểm tra:**

| Kiểm tra | Điều kiện đạt |
|---|---|
| Gọi API thành công | HTTP 200, `stop_reason` = `end_turn` (không phải `refusal` hay `max_tokens`) |
| Đọc được JSON | Phân tích được JSON theo schema |
| Độ dài Facebook (loại E, P, N) | `message` 80–2.000 ký tự, tối đa 8 hashtag |
| Độ dài Pinterest (loại E, P) | `title` 10–100 ký tự; `description` 50–500; `alt_text` 5–500 |
| Cụm từ bị cấm | Không chứa: chữa khỏi / chữa bệnh / chữa trị, điều trị, đặc trị, khỏi hẳn, khỏi bệnh, hết bệnh, dứt điểm, thay thế thuốc, 100%, cam kết, thần dược, số 1, tốt nhất, duy nhất, giảm cân. Câu khuyến cáo do hệ thống gắn được bỏ ra trước khi dò. |

**Quyết định cho từng kênh:**

| Loại | Facebook | Pinterest |
|---|---|---|
| E – Kiến thức | Đăng | Đăng |
| P – Sản phẩm | Đăng, code gắn câu khuyến cáo thành dòng riêng | Đăng, câu khuyến cáo ở cuối mô tả |
| N – Tin thương hiệu | Đăng | Bỏ qua ("Tin thương hiệu chỉ đăng Facebook") |
| X – Không đồng bộ | Bỏ qua, ghi lý do của AI | Bỏ qua |

Với lượt được đăng, kết quả đi theo chế độ: **Chờ duyệt** (mặc định), **Tự đăng theo lịch**, hoặc **Chạy thử** (chỉ trả kết quả về trang Chạy thử, không đăng).

**Ghép nội dung cuối:**
- **Facebook:** lời AI viết → câu khuyến cáo (chỉ loại P) → dòng `Giá: …` (lấy từ WooCommerce) → `👉 Xem chi tiết: <link có utm_source=facebook>` → hashtag.
- **Pinterest:** tiêu đề AI; mô tả AI + giá + câu khuyến cáo (loại P); alt text AI; board theo bảng ghép danh mục; link có `utm_source=pinterest`.

**Khi không đạt:** giữ nội dung **mẫu cố định**, ghi `content_source = template` và lý do cụ thể. Ví dụ đã ghi nhận: `Dùng mẫu cố định vì AI không đạt: Claude trả HTTP 401 (authentication_error)…`.

Mô hình, số token và thời gian gọi được lưu vào từng lượt. Trang Báo cáo cộng lại thành chi phí AI ước tính.

### 1.6 Prompt báo cáo tuần (nguyên văn)

```text
Viết báo cáo tuần ngắn (tối đa 200 từ, tiếng Việt) cho trưởng phòng Marketing về hệ thống tự động đồng bộ nội dung website lên Facebook và Pinterest.
Chỉ dùng số trong dữ liệu. Không suy diễn nguyên nhân khi dữ liệu không cho biết. Không dùng markdown, chỉ văn bản thường với gạch đầu dòng "-".
Cấu trúc: 1) Kết quả chính (số bài, tỉ lệ thành công từng kênh). 2) Lỗi và việc chờ xử lý (chờ duyệt, lỗi). 3) Bài có tương tác cao nhất. 4) Việc cần người quyết định (nếu có).
```

User message là JSON số liệu do WordPress tính:
- số lượt theo kênh và trạng thái, tỉ lệ thành công, thời gian trung bình từ lúc xuất bản tới lúc bài lên kênh;
- phân loại, nguồn nội dung, token và chi phí;
- 5 bài Facebook có tương tác cao nhất;
- ghi chú lỗi khi đọc tương tác.

Nếu Claude lỗi, hệ thống vẫn lưu báo cáo dạng số liệu thô kèm lý do.

---

## Nhóm 2 – Prompt bổ sung cho bản chính thức (thiết kế, chưa chạy thử)

### 2.1 Viết bài sản phẩm theo sổ xác nhận quảng cáo (XNQC)

Website doppelherz.vn đã có sẵn "sổ công dụng được phép nói": 34/36 trang sản phẩm ghi số XNQC và 2–3 dòng công dụng. Prompt khóa AI vào đúng các dòng này.

```text
SYSTEM: (giữ toàn bộ prompt 1.2, thêm:)
- Bài này nhắc tới sản phẩm. Công dụng CHỈ được lấy nguyên văn từ danh sách "cong_dung_duoc_phep" dưới đây.
  Không diễn giải rộng hơn, không gộp hai công dụng thành một khẳng định mạnh hơn.
- Không viết số XNQC và cụm "Thực phẩm bảo vệ sức khỏe": hệ thống tự gắn khối pháp lý cuối bài.

USER:
{
  "bai_goc": { ...như mục 1.3... },
  "san_pham": {
    "ten": "TPBVSK Vitamin C 1000",
    "cong_dung_duoc_phep": [
      "Cung cấp vitamin C và vitamin D3",
      "Hỗ trợ tăng cường sức đề kháng",
      "Hỗ trợ tăng cường sức khỏe"
    ]
  }
}
```

Code gắn khối pháp lý cố định vào cuối bài. Ví dụ với dữ liệu lấy từ trang sản phẩm Vitamin C 1000 trên doppelherz.vn:

```text
Thực phẩm bảo vệ sức khỏe Vitamin C 1000
Số XNQC: 799/2023/XNQC-ATTP
Thực phẩm này không phải là thuốc, không có tác dụng thay thế thuốc chữa bệnh.
```

### 2.2 Rà soát tuân thủ (lớp thứ hai sau bộ lọc từ khóa)

Bộ lọc từ khóa bắt được từ cấm, nhưng không bắt được ý ngầm, ví dụ "uống là hết mệt ngay". Một lần gọi riêng đóng vai người kiểm duyệt.

```text
SYSTEM:
Bạn là người rà soát nội dung quảng cáo thực phẩm bảo vệ sức khỏe tại Việt Nam.
Đối chiếu NỘI DUNG MẠNG XÃ HỘI với BÀI GỐC và DANH SÁCH CÔNG DỤNG ĐƯỢC PHÉP. Chỉ ra mọi chỗ:
1. Nêu công dụng, số liệu, thành phần không có trong bài gốc hoặc danh sách công dụng.
2. Ngụ ý chữa bệnh, thay thế thuốc, hứa hẹn kết quả hoặc thời gian có hiệu quả.
3. So sánh nhất, so sánh với sản phẩm khác.
4. Gán tình trạng bệnh cho người đọc; nói về giảm cân; trước–sau.
5. Dùng danh nghĩa bác sĩ, dược sĩ, cơ sở y tế, lời chứng thực của người dùng.
Không sửa nội dung. Chỉ báo lỗi, trích nguyên văn đoạn vi phạm. Không có lỗi thì trả danh sách rỗng.

USER: {"bai_goc": "...", "cong_dung_duoc_phep": [...], "noi_dung_mxh": {"facebook": "...", "pinterest": {...}}}

SCHEMA: {"dat": true|false, "loi": [{"quy_tac": 1-5, "trich_dan": "string", "giai_thich": "string"}]}
```

Kết quả rà soát được hiện ngay trên màn hình duyệt, cạnh cảnh báo từ khóa đang có. Lỗi nặng thì nội dung quay về bước viết lại, tối đa một lần.

### 2.3 Chữ trên ảnh Pin dọc (2:3)

Kênh Pinterest của Doppelherz Đức dùng ba bố cục lặp lại. Ảnh do code dựng từ mẫu thiết kế có sẵn, **AI chỉ viết chữ**. AI không tạo ảnh, để tránh hình ảnh y tế hoặc người thật trái quy định.

```text
SYSTEM:
Viết chữ in trên ảnh Pin cho bài kiến thức sức khỏe. Ba bố cục:
- A "câu hỏi + lời hứa": cau_hoi ≤ 40 ký tự, loi_ich ≤ 60 ký tự.
- B "dải tiêu đề": tieu_de_in_hoa ≤ 45 ký tự, dong_phu ≤ 50 ký tự, huy_hieu ≤ 15 ký tự (chủ đề, ví dụ "OMEGA-3").
- C "sản phẩm": ten_san_pham + mot_dong_mo_ta ≤ 50 ký tự lấy từ công dụng được phép.
Chọn bố cục phù hợp nhất với bài: A cho bài giải đáp, B cho bài mẹo/hướng dẫn, C chỉ khi bài loại P.
Cùng nguyên tắc nội dung như prompt viết bài. Không dùng dấu chấm than.

USER: {"loai": "E|P", "tieu_de": "...", "y_chinh": ["...", "..."], "cong_dung_duoc_phep": [...]}

SCHEMA: {"bo_cuc": "A|B|C", "chu": {"...": "..."}}
```

---

## Kết quả kiểm thử bộ prompt (19/11/2026)

| Bài thử | Kết quả mong đợi | Kết quả thật |
|---|---|---|
| Bài kiến thức "Omega-3 và sức khỏe tim mạch" | E, viết 2 kênh, không câu khuyến cáo | **Đạt**: E, chờ duyệt, duyệt xong đăng cả 2 kênh |
| Sản phẩm "Viên uống Vitamin C 1000 mg" | P, câu khuyến cáo thành dòng riêng, giá do code gắn | **Đạt**: P, khuyến cáo trước dòng "Giá: 185.000 ₫" |
| "Thông báo chiết khấu quý 4 cho nhà thuốc và đại lý" | X, không đăng | **Đạt**: AI phân loại X, cả 2 kênh Bỏ qua |
| Bài thuộc danh mục "Tin nội bộ" | Chặn ở WordPress, không gọi AI | **Đạt** |
| "Công ty đồng hành khám sức khỏe miễn phí" | N, chỉ Facebook | **Đạt**: Facebook chờ duyệt, Pinterest Bỏ qua |
| 5 bài mới nhất của doppelherz.vn (chạy thử) | Phân loại hợp lý, không đăng | **Đạt**: 2 bài chặn theo "Tin nội bộ"; danh sách trúng thưởng cho đại lý → X; 2 tin hội thảo, đào tạo → N |
| API key sai | Mẫu cố định + lý do | **Đạt**: "HTTP 401" |

Bộ bài thử đặt cố định. Mỗi lần sửa prompt thì chạy lại toàn bộ và so sánh kết quả, không sửa theo cảm tính. Với doanh nghiệp thật, bộ này mở rộng thành 20 bài mẫu do Marketing và người phụ trách pháp lý (khách chỉ định) [Giả định] chọn.

## Nguồn
- Prompt và code kiểm tra: `n8n/workflows/socialsync-main.json` (khối "AI: dựng yêu cầu", "AI: kiểm tra và áp nội dung"), `n8n/workflows/socialsync-report.json` (khối "Dựng yêu cầu tóm tắt")
- Kết quả kiểm thử: nhật ký `logs/social-sync.log`, bảng `wp_socialsync_jobs` và `wp_socialsync_previews`, ngày 19/11/2026
- Ràng buộc ngành và sổ XNQC: `research/02_template_3_kenh_va_huong_prototype.md` mục 2 và 4, `research/doppelherz_san_pham_xnqc.csv`. Phần pháp lý là tóm tắt từ nguồn thứ cấp, không thay cho ý kiến pháp lý của doanh nghiệp.
- Ba bố cục Pin: khảo sát kênh Pinterest Doppelherz Đức trong `research/02_template_3_kenh_va_huong_prototype.md` mục 1.3 và 2.3
