# 2.4 Sơ đồ quy trình hệ thống SocialSync

Tài liệu có hai phần:
- **Phần A – Bản demo đang chạy thật.** Mọi sơ đồ khớp với code và luồng n8n đã triển khai tại `shop.adaptivealgo.uk` và `n8n.adaptivealgo.uk`. Các con số là số đo ngày 19/11/2026.
- **Phần B – Bản chính thức đề xuất cho Doppelherz.** Gồm những bước còn thiếu so với demo: tra sổ xác nhận quảng cáo, rà soát tuân thủ lớp hai, ảnh Pin dọc, hai lớp duyệt. Đây là phạm vi của dự án thật trong kế hoạch và báo giá.

Sơ đồ viết bằng Mermaid: GitHub và VS Code hiển thị trực tiếp thành hình.

---

## Phần A – Bản demo đang chạy thật

### A1. Kiến trúc tổng thể

```mermaid
flowchart LR
  subgraph NguoiDung["Người dùng"]
    BT["Người viết<br/>đăng bài / sản phẩm"]
    ND["Người duyệt<br/>(Marketing)"]
  end

  subgraph MayChu["Máy chủ tự host (Docker)"]
    WP["WordPress + WooCommerce<br/>plugin SocialSync"]
    DB[("MariaDB<br/>lượt đồng bộ, chạy thử")]
    N8N["n8n tự host<br/>luồng chính · báo lỗi · báo cáo tuần"]
  end

  subgraph BenNgoai["Dịch vụ bên ngoài"]
    CL["Claude API<br/>claude-opus-5-5"]
    FB["Facebook Graph API<br/>Fanpage"]
    MK["Make.com<br/>scenario SocialSync - Pinterest"]
    PI["Pinterest"]
    DH["doppelherz.vn<br/>REST API công khai (chỉ đọc)"]
  end

  CF["Cloudflare Tunnel<br/>shop. / n8n.adaptivealgo.uk"]

  BT -- HTTPS --> CF --> WP
  ND -- "duyệt / sửa / từ chối" --> CF
  WP <--> DB
  WP -- "webhook nội bộ + mã bí mật" --> N8N
  N8N -- "báo trạng thái từng lượt" --> WP
  N8N --> CL
  N8N --> FB
  N8N -- webhook --> MK --> PI
  WP -- "trang Chạy thử" --> DH
```

Điểm thiết kế chính:
- **Plugin WordPress lo phần con người cần nhìn và bấm.** Nhật ký, màn hình duyệt, lịch đăng, báo cáo, chạy thử. **n8n lo phần máy làm:** gọi AI, kiểm tra, chờ giờ, đăng, đọc tương tác. Sửa quy trình đăng không phải sửa website.
- **WordPress và n8n gọi nhau qua mạng nội bộ Docker**, có header `X-SocialSync-Secret`, và mọi bước dùng chung một mã yêu cầu nên truy được bài nào đi qua đâu.
- **Pinterest đi qua Make.com.** App Pinterest API riêng mới ở mức Trial: Pin chỉ người tạo thấy, và việc xét duyệt mất 1–4 tuần. Kết nối có sẵn của Make tạo được Pin công khai, đã kiểm tra bằng cách mở Pin khi không đăng nhập.

### A2. Một bài đi qua hệ thống (chế độ mặc định: Duyệt bắt buộc)

```mermaid
sequenceDiagram
  autonumber
  actor U as Người viết
  participant WP as WordPress (plugin)
  participant N as n8n
  participant C as Claude
  actor R as Người duyệt
  participant F as Facebook
  participant M as Make → Pinterest

  U->>WP: Bấm "Đăng"
  WP->>WP: Chặn sớm? (danh mục bị chặn, tiêu đề .pdf) → Bỏ qua, không gọi AI
  WP->>WP: Tạo 1 lượt mỗi kênh + xếp giờ đăng<br/>(khung giờ, tối đa/ngày, cách nhau ≥ 30 phút)
  WP-->>U: Trả trang ngay (gửi nền 1–2 ms)
  WP->>N: Dữ liệu bài + giờ đăng + chế độ duyệt
  N->>C: 1 lần gọi: phân loại E/P/N/X + viết nội dung 2 kênh
  C-->>N: JSON
  N->>N: Kiểm tra độ dài, từ cấm; gắn khuyến cáo (loại P), giá, link UTM
  N->>WP: Từng lượt: "Chờ duyệt" (hoặc "Bỏ qua" nếu X / N trên Pinterest)
  R->>WP: Trung tâm SocialSync: xem, sửa nội dung, chọn giờ → Duyệt
  WP->>WP: Khóa lượt (chỉ đổi nếu còn "Chờ duyệt") + cấp mã lệnh
  WP->>N: Lệnh đăng đã duyệt (kèm mã lệnh)
  N->>N: Chờ tới giờ đăng của từng kênh
  opt Người duyệt đổi giờ / hủy lịch trong lúc chờ
    R->>WP: Đổi giờ (mã lệnh mới, gửi lệnh mới) hoặc Hủy lịch (về Chờ duyệt)
  end
  N->>WP: Còn đăng lượt này không? (mã lệnh)
  WP-->>N: Có (và nhận lượt) / Không (đã đổi giờ, hủy, bài bị gỡ)
  N->>F: Đăng ảnh + nội dung
  N->>WP: Facebook: "Đã đăng" + link
  N->>M: Tiêu đề, mô tả, ảnh, link, board
  M-->>N: pin_id
  N->>WP: Pinterest: "Đã đăng" + link Pin
```

Số đo:
- **Không có bước duyệt** (bài #66): Facebook lên sau 7 giây; Pinterest đặt trễ 1 phút, lên sau 62 giây.
- **Có bước duyệt** (bài #74): tài khoản người chấm bấm "Duyệt và đăng ngay", cả hai kênh lên trong vòng 20 giây.
- **Claude:** 5–17 giây cho mỗi lần gọi.

### A3. Luồng n8n chính (33 khối, hai đường vào)

```mermaid
flowchart TD
  W["Nhận bài từ WordPress"] --> K["Kiểm tra dữ liệu<br/>202 / 422 / gói test / chạy thử"]
  K --> R["Trả lời WordPress"] --> T["Tách lượt + nội dung mẫu<br/>giờ đăng do WordPress xếp"]
  T --> A1["AI: dựng yêu cầu"] --> A2["AI: Claude phân loại + viết"] --> A3["AI: kiểm tra và áp nội dung<br/>quyết định: bỏ qua / chờ duyệt / đăng / chạy thử"]
  A3 --> D{"Chạy thử?"}
  D -- có --> D1["Chạy thử: gom kết quả"] --> D2["Gửi kết quả về trang Chạy thử"]
  D -- không --> P{"Đăng theo lịch luôn?<br/>(chế độ Tự đăng)"}
  P -- không --> H["Báo WordPress:<br/>chờ duyệt / bỏ qua"]
  P -- có --> G["Gom lượt cần đăng"]
  AW["Nhận lệnh đăng đã duyệt<br/>(webhook thứ hai)"] --> AB["Dựng lượt đã duyệt"] --> G
  G --> S["Báo WordPress: đã lên lịch"] --> X["Xác nhận WordPress đã nhận<br/>(bị từ chối → dừng, chống đăng trùng)"]
  X --> L{{"Lần lượt từng kênh"}}
  L -- "từng lượt" --> WT["Chờ tới giờ đăng"] --> ASK["Hỏi WordPress: còn đăng không?<br/>(mã lệnh / giờ hẹn)"] --> GO{"Còn đăng?"}
  GO -- "không: đã hủy / đổi giờ" --> L
  GO -- "không trả lời rõ" --> FAILCB["Báo lượt Lỗi, không đăng"] --> L
  GO -- có --> IF{"Facebook?"}
  IF -- có --> FB1["Facebook: đăng bài"] --> FB2["Facebook: đọc kết quả"]
  IF -- không --> P0{"Pinterest có ảnh?"}
  P0 -- có --> P1["Gửi sang Make"] --> P2["Đọc kết quả"]
  P0 -- không --> P3["Bỏ qua (không có ảnh)"]
  FB2 --> CB["Báo WordPress: kết quả"]
  P2 --> CB
  P3 --> CB
  CB --> L
  L -- "xong hết" --> SUM["Tổng kết lượt chạy"]
```

Hai luồng phụ:

```mermaid
flowchart LR
  subgraph Loi["SocialSync - Báo lỗi"]
    E["Khi luồng chính lỗi"] --> S1["Tóm tắt lỗi"] --> W1["WordPress: đánh dấu lượt còn mở là Lỗi"]
  end
  subgraph BaoCao["SocialSync - Báo cáo tuần"]
    T1["Thứ Hai 08:00<br/>hoặc nút trên WordPress"] --> G1["Lấy số liệu WordPress"] --> F1["Facebook: đọc tương tác<br/>(1 request batch)"] --> M1["Lưu tương tác"] --> C1["Claude tóm tắt"] --> R1["Lưu báo cáo"]
  end
```

### A4. Vòng đời một lượt đồng bộ

```mermaid
stateDiagram-v2
  [*] --> skipped: Bị chặn ở WordPress (danh mục, PDF)
  [*] --> pending: Bài xuất bản lần đầu
  pending --> needs_review: AI viết xong (Duyệt bắt buộc)
  pending --> scheduled: AI viết xong (Tự đăng)
  pending --> skipped: AI phân loại X / tin thương hiệu hoặc bài không ảnh trên Pinterest
  pending --> failed: n8n không nhận / luồng lỗi
  needs_review --> scheduled: Người duyệt bấm Duyệt
  needs_review --> rejected: Người duyệt từ chối / bỏ kênh
  scheduled --> needs_review: Hủy lịch (giữ nội dung đã duyệt)
  scheduled --> scheduled: Đổi giờ / Đăng ngay (mã lệnh mới)
  scheduled --> published: Kênh trả link bài
  scheduled --> failed: Kênh từ chối / luồng lỗi
  scheduled --> skipped: Pin không có ảnh (lớp chặn cuối)
  failed --> pending: "Cho AI viết lại" / "Đồng bộ lại"
  failed --> scheduled: "Đăng lại nội dung đã duyệt" (không gọi AI)
  published --> [*]
```

Bốn lớp chống đăng trùng / đăng sai:
1. Khóa duy nhất (bài, kênh) trong cơ sở dữ liệu.
2. Nút Duyệt, Đồng bộ lại, Đổi giờ, Hủy lịch chỉ đổi lượt còn ở đúng trạng thái vừa đọc (UPDATE có điều kiện). Đã thử bấm duyệt lần hai: bị chặn.
3. n8n chỉ đăng khi WordPress chấp nhận chuyển lượt sang "Đã lên lịch".
4. Ngay trước khi đăng, n8n hỏi lại WordPress với **mã lệnh** của lần gửi. WordPress chỉ trả "đăng" khi lượt còn Đã lên lịch, mã lệnh khớp (lượt Tự đăng: giờ hẹn khớp), bài còn xuất bản và không bị chặn; đồng thời "nhận lượt" để Hủy lịch bấm cùng lúc không thắng được. Đã thử 11 tình huống ở tầng dữ liệu (20/11): sai/thiếu mã → không đăng; đúng mã → đăng một lần, hỏi lần hai bị từ chối; hủy khi đã nhận → bị chặn; hủy rồi lần chạy cũ hỏi → không đăng; Tự đăng đổi giờ → không đăng; sai mã bí mật → 401.

### A5. Màn hình trong WordPress (menu SocialSync)

Bản 0.4.0 gộp *Chờ duyệt*, *Xem và duyệt* và *Nhật ký* thành **một trang: Trung tâm SocialSync**. Lý do và số đo trước/sau: xem đề xuất UX (đếm từ code): duyệt N bài từ 2N+1 xuống N+1 lần bấm; đăng → duyệt → xem kết quả từ 4 màn hình xuống 2 (trình soạn + Trung tâm).

| Màn hình | Ai dùng | Làm gì |
|---|---|---|
| **Trung tâm SocialSync** (menu chính, có số bài chờ duyệt) | Quản lý cửa hàng, Biên tập, Quản trị | Trái: danh sách *bài* với chip trạng thái từng kênh, tab Cần duyệt / Đã lên lịch / Đã đăng / Lỗi / Bỏ qua–Từ chối / Tất cả, tìm theo tên (không dấu) hoặc #ID. Phải: phân loại + lý do, nguồn nội dung, ảnh; form duyệt (sửa, bỏ kênh, chọn giờ, duyệt / đăng ngay, lý do từ chối chọn nhanh, duyệt xong tự mở bài kế); thẻ kết quả từng kênh (link bài, tương tác, lỗi đầy đủ, Đăng lại nội dung đã duyệt / Cho AI viết lại, **đổi giờ / đăng ngay / hủy lịch sau khi duyệt**); dòng thời gian. Tự cập nhật 15 giây khi có lượt đang xử lý, trừ khi đang sửa form |
| Bảng **Mạng xã hội (SocialSync)** trong màn hình viết bài | Người viết | Trước khi đăng: chọn giờ từng kênh, để trống thì tự xếp. Sau khi bấm Đăng: hiện trạng thái từng kênh (tự hỏi lại mỗi 10 giây khi AI đang viết) và nút mở đúng bài trong Trung tâm |
| Cột **Mạng xã hội** ở danh sách Bài viết / Sản phẩm | Biên tập | Chip trạng thái từng kênh, bấm vào mở bài trong Trung tâm |
| **Báo cáo** | Như Trung tâm | 7/30 ngày: tỉ lệ thành công từng kênh, thời gian lên kênh, AI hay mẫu, chi phí AI ước tính, số bài theo ngày, tương tác cao nhất, báo cáo tuần do Claude tóm tắt |
| **Nhật ký chi tiết** | Kỹ thuật, báo cáo | Tra cứu theo ngày / kênh / trạng thái, mỗi lượt một dòng, bảng tổng hợp |
| **Chạy thử doppelherz.vn** | Như Trung tâm | Đưa 3/5/10 bài thật mới nhất qua đúng luồng AI, không đăng |
| **Cấu hình** | Chỉ Quản trị | Chế độ duyệt, khung giờ, tối đa/ngày, khoảng cách, danh mục chặn, câu khuyến cáo, board Pinterest, webhook, mã xác minh Pinterest, mức log |

Wireframe Trung tâm SocialSync:

```
┌─ Trung tâm SocialSync ───────────────────────── Chế độ: Duyệt bắt buộc · 07:00–22:00 · ≤10/ngày ─┐
│ [Cần duyệt 2] [Đã lên lịch] [Đã đăng 6] [Lỗi] [Bỏ qua/Từ chối 4] [Tất cả]   🔍 Tìm tên bài / #ID │
├──────────────────────────────┬───────────────────────────────────────────────────────────────────┤
│ ▶ Vitamin D mùa đông…         │ Vitamin D mùa đông…   Bài viết #86 · Sửa bài · Xem trên website   │
│   FB ◐ Chờ duyệt  Pin ◐ …     │ [Phân loại: Kiến thức + lý do] [Nguồn: Claude, token, giây] [Ảnh] │
│   Viên uống Vitamin C…        │ ┌ [x] Facebook ─ ◐ Chờ duyệt ┐ ┌ [x] Pinterest ─ ◐ Chờ duyệt ┐      │
│   FB ◐  Pin ◐                 │ │ nội dung (đếm ký tự,        │ │ tiêu đề / mô tả / alt       │      │
│   5 thói quen ngủ ngon…       │ │ tô từ cấm khi gõ) · giờ     │ │ board · giờ đăng            │      │
│   FB ● Đã đăng  Pin — thiếu ảnh│ └────────────────────────────┘ └─────────────────────────────┘      │
│                               │ [Duyệt theo giờ] [Duyệt và đăng ngay] ☑ mở bài chờ duyệt tiếp theo │
│                               │ Lý do từ chối: (Bài nội bộ)(Sai thông tin SP)(Cần duyệt pháp lý)… [Từ chối]│
│                               │ ▸ Dòng thời gian                                                   │
└──────────────────────────────┴───────────────────────────────────────────────────────────────────┘
Đã lên lịch: thẻ kênh có [giờ mới][Lưu giờ mới] [Đăng ngay] [Hủy lịch] · Lỗi: [Đăng lại nội dung đã duyệt] [Cho AI viết lại]
```

---

## Phần B – Bản chính thức đề xuất cho Doppelherz

Các bước ghi "(chưa có)" là phần demo chưa làm.

```mermaid
flowchart TD
  P["Bài mới trên doppelherz.vn"] --> C{"Chặn sớm + Claude phân loại"}
  C -- "X" --> STOP["Không đồng bộ, ghi lý do"]
  C -- "E / N" --> G
  C -- "P" --> XN["Tra sổ XNQC (chưa có)<br/>công dụng được phép nói"] --> G
  G["Claude viết nội dung theo kênh"] --> CK1["Kiểm tra luật cứng<br/>(độ dài, từ cấm)"] --> CK2["Claude rà soát tuân thủ lớp 2 (chưa có)"]
  CK2 --> IMG["Dựng ảnh Pin dọc 2:3 (chưa có)"]
  IMG --> Q["Trung tâm: chờ duyệt (Marketing)"]
  Q --> Q2{"Nhóm nhạy cảm?"}
  Q2 -- có --> LG["Người phụ trách pháp lý duyệt lớp 2 (chưa có)"] --> SC
  Q2 -- không --> SC["Xếp lịch rải đều"]
  SC --> PUB["Đăng Facebook / Pinterest"] --> LOG["Nhật ký"] --> REP["Báo cáo tuần<br/>+ lượt truy cập UTM qua GA4 (chưa có)"]
```

| Bước | Demo (đã chạy) | Bản chính thức (thêm) |
|---|---|---|
| Chọn bài | Chặn theo danh mục, PDF; Claude phân loại E/P/N/X | Danh mục chặn theo cấu trúc của doppelherz.vn; đo độ chính xác phân loại trên 20 bài mẫu |
| Nội dung | Claude viết từ bài gốc, cấm từ rủi ro, khuyến cáo do code gắn | Tra sổ XNQC cho bài sản phẩm; rà soát tuân thủ lớp 2; bộ luật là file người phụ trách pháp lý (khách chỉ định) [Giả định] sửa được |
| Duyệt | Một lớp, sửa được nội dung và giờ, chống duyệt trùng | Hai lớp cho nhóm nhạy cảm (hiện chỉ gắn cờ); nhắc việc duyệt qua email |
| Ảnh Pinterest | Ảnh đại diện có sẵn | Ảnh dọc 1000×1500 theo 3 bố cục |
| Lịch | Tự rải theo khung giờ, số bài/ngày, khoảng cách; giờ chọn tay | Lịch nội dung trên Google Sheet nếu khách cần |
| Báo cáo | Trang Báo cáo + báo cáo tuần do Claude tóm tắt | Thêm GA4 (lượt truy cập theo UTM); số liệu Pin cần API Pinterest riêng (tương tác Facebook đã đọc được) |

## Nguồn
- Code và luồng đang chạy: `plugin/social-sync/`, `n8n/workflows/socialsync-main.json`, `socialsync-error.json`, `socialsync-report.json` trong repo https://github.com/jinsimple/doppelherz-social
- Số đo: nhật ký `logs/social-sync.log`, bảng `wp_socialsync_jobs`, ngày 19/11/2026
- Khảo sát doppelherz.vn và lưu ý ngành: `research/01_phan_tich_de_bai.md`, `research/02_template_3_kenh_va_huong_prototype.md`
