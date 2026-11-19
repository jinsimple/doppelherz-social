# Doppelherz VN – Template 3 kênh, hướng prototype 24h và lưu ý ngành TPBVSK

Ngày khảo sát: 2026-11-19. Ký hiệu nguồn: **[Đo]** = số liệu tôi đo trực tiếp trong phiên; **[Nguồn]** = từ tài liệu/báo/bài tổng hợp chính sách (thứ cấp); **[Chưa kiểm]** = hiểu biết chung, chưa kiểm chứng trong phiên, cần xác nhận trước khi đưa vào bài nộp.

## 1. Hiện trạng ba kênh

### 1.1 Website doppelherz.vn (nguồn nội dung gốc) [Đo – 40 bài "Kiến thức sức khỏe" mới nhất, 36 sản phẩm]
- Độ dài 1.169–3.035 từ (trung vị 1.812); trung vị 4 thẻ H2, 4 ảnh; tiêu đề trung vị 65 ký tự; đoạn tóm tắt ~250 ký tự.
- Ảnh đại diện chủ yếu ngang 3:2, phổ biến nhất 600×400 px (19/40) → không dùng thẳng làm Pin dọc 2:3 được.
- 40/40 bài nhắc thương hiệu và có khối chân bài cố định: hotline 1800 1770, Zalo OA, link "Mua hàng" qua bom.so. 0/40 bài có tham số UTM → không đo được kênh nào mang về đơn.
- 17/40 bài có link tới trang sản phẩm; 37/40 có câu "không phải là thuốc"; 0/40 có mục tài liệu tham khảo.
- Trang sản phẩm: 36/36 có câu khuyến cáo; 34/36 ghi số xác nhận quảng cáo dạng `799/2023/XNQC-ATTP` kèm số ĐKSP và 2–3 dòng công dụng (2 sản phẩm còn lại có nhắc XNQC nhưng không khớp định dạng số). Năm cấp XNQC: 2020 (10), 2021 (4), 2022 (8), 2023 (10), 2024 (2).
  → Website đã có sẵn "sổ công dụng được phép nói" cho từng sản phẩm. Đã trích thành `doppelherz_san_pham_xnqc.csv`.

### 1.2 Facebook DoppelherzVietnam
- Trang chính thức, website liên kết tới; ~628 nghìn người theo dõi [Nguồn – kết quả tìm kiếm].
- **Chưa đo được** nội dung, tần suất, tương tác: Facebook không cho đọc công khai bằng công cụ. Nếu bạn xuất được 20–30 bài gần nhất (Meta Business Suite hoặc chụp màn hình), tôi phân tích bổ sung.

### 1.3 Pinterest Doppelherz Đức (doppelherz_de) – kênh tham chiếu [Đo]
- 358 người theo dõi, 173 Pin, 17 board; tạo 25/06/2017; lần lưu Pin gần nhất 30/07/2025; tên miền chưa xác minh.
- 19 Pin gần nhất (02–07/2025): 2–4 Pin/tháng, đăng thứ Hai–thứ Năm lúc 10–11h UTC → dấu hiệu lên lịch sẵn.
- Mô tả Pin 153–380 ký tự (trung vị 298); 14/19 kết bằng lời mời "…hier" (xem tại đây); 0/19 dùng hashtag; chỉ 2/19 nhắc tên Doppelherz.
- 10 board đọc được, đặt theo **nhu cầu sức khỏe** chứ không theo sản phẩm: Omega-3 (25), Khoáng chất & Vitamin (22), Da + Tóc + Móng (17), Dạ dày & Tiêu hóa (14), Miễn dịch (14), Mẹo dinh dưỡng (9), Mắt (8), Năng lượng (7), Thuần chay (5), Mẹ + Bé (3).
- 12 ảnh Pin mới nhất: tất cả tỉ lệ 2:3; 11 Pin kiến thức, 1 Pin sản phẩm; ba bố cục lặp lại (xem `pinterest_de_12_pin_mau.jpg`).
- **Nhận định:** kênh Đức nhỏ và đã ngừng đăng hơn một năm. Dùng được làm mẫu về bố cục và giọng văn, không dùng được làm bằng chứng rằng Pinterest là kênh hiệu quả. Trong bài nộp nên định vị Pinterest là kênh phụ, chi phí thấp, tái sử dụng kho bài cũ.

## 2. Template đề xuất

### 2.0 Phân loại nội dung – quyết định bài nào đi đâu
| Loại | Dấu hiệu trên website | Facebook | Pinterest | Mức duyệt |
|---|---|---|---|---|
| E – Kiến thức | Danh mục Kiến thức sức khỏe, Làm đẹp, Xương khớp…; không nêu công dụng sản phẩm trong bài social | Có | Có (ưu tiên) | 1 người duyệt |
| P – Sản phẩm | Bài/trang có tên sản phẩm + công dụng | Có | Hạn chế (≤1/10 Pin như kênh Đức) | Marketing + pháp chế; chỉ dùng công dụng trong sổ XNQC |
| N – Tin thương hiệu | Sự kiện, CSR, báo chí | Có | Không | 1 người duyệt; bài có hình ảnh/danh nghĩa nhân viên y tế → pháp chế |
| X – Không đồng bộ | Thông báo chương trình cho đại lý (PDF), Tin nội bộ | Không | Không | Chặn tự động |

### 2.1 Website (bổ sung trường dữ liệu, không đổi cách viết bài)
- `content_type` (E/P/N/X), `product_ids` (nếu nhắc sản phẩm), 3 ý chính (dùng lại cho Facebook và Pin), 1 ảnh dọc 1000×1500, mục tài liệu tham khảo, link mua hàng có UTM theo kênh.

### 2.2 Facebook – khung caption
1. Câu mở: nêu chủ đề hoặc câu hỏi chung, không gán tình trạng sức khỏe cho người đọc ("Trẻ hay ốm vặt – vì sao?" thay cho "Con bạn đang bị…").
2. 2–3 ý chính lấy từ bài gốc, không thêm số liệu hay công dụng mới.
3. Lời mời đọc + link bài có `utm_source=facebook`.
4. Chỉ với loại P: khối cố định gồm tên sản phẩm kèm cụm "Thực phẩm bảo vệ sức khỏe", công dụng đúng nguyên văn sổ XNQC, số XNQC, câu "Thực phẩm này không phải là thuốc và không có tác dụng thay thế thuốc chữa bệnh."
5. 2–3 hashtag theo chủ đề.
- Ảnh: 1080×1350 (4:5) dựng từ cùng bộ template với Pin, hoặc link preview 1200×630. [Chưa kiểm – kích thước chuẩn nền tảng]

### 2.3 Pinterest – ba bố cục theo kênh Đức, tỉ lệ 2:3 (1000×1500)
- **A – Câu hỏi + lời hứa:** ảnh đời sống tràn khung; nhãn đỏ chứa câu hỏi ngắn; ô trắng chữ đỏ chứa lợi ích của bài. Dùng cho bài kiến thức.
- **B – Dải tiêu đề:** dải đỏ phía trên với tiêu đề IN HOA + một dòng phụ; huy hiệu tròn xanh ghi chủ đề; ảnh phía dưới. Dùng cho bài dạng "mẹo/hướng dẫn".
- **C – Sản phẩm:** ảnh trên, hộp sản phẩm ở giữa, dải đỏ phía dưới ghi tên sản phẩm + một dòng mô tả. Thêm cụm TPBVSK + khuyến cáo ngay trên ảnh.
- Văn bản Pin: tiêu đề ≤100 ký tự; mô tả 150–400 ký tự kết bằng lời mời đọc bài; alt-text mô tả ảnh; link bài có `utm_source=pinterest`. [Chưa kiểm – giới hạn ký tự của nền tảng]
- Board theo nhu cầu sức khỏe, ánh xạ từ danh mục website: Vitamin & Khoáng chất (267 bài), Sức khỏe trẻ em (353), Mẹ bầu (202), Làm đẹp (179), Xương khớp (105), Sức khỏe phụ nữ (96), Tăng sức đề kháng (56).

## 3. Hướng prototype phù hợp nhất trong 24h

**Đề xuất: "Dây chuyền nội dung có cổng tuân thủ"** – tự động hóa phần viết, dựng ảnh, xếp lịch, báo cáo; giữ con người ở bước duyệt.

Luồng: đọc bài thật từ doppelherz.vn → phân loại E/P/N/X → (P) tra sổ XNQC → Claude viết caption Facebook + tiêu đề/mô tả/alt-text Pin → bộ kiểm tuân thủ (luật cứng + AI rà) → dựng ảnh Pin 2:3 theo template A/B/C → hàng chờ duyệt → xếp lịch → đăng → log + báo cáo.

Vì sao hướng này hợp doanh nghiệp TPBVSK hơn "đăng tự động hoàn toàn":
- Mức phạt cho một lỗi quảng cáo (20–40 triệu) ngang ngân sách cả dự án; fanpage có ~628 nghìn người theo dõi. Giá trị nằm ở tốc độ + nhất quán + không sai, không phải ở việc bỏ người duyệt.
- Dữ liệu cần cho cổng tuân thủ đã có sẵn trên website (sổ XNQC), nên làm được thật trong 24h.
- Giải được đúng các điểm đau đã đo: đăng theo đợt, lẫn thông báo đại lý, ảnh ngang không hợp Pinterest, không có UTM.

Cái gì chạy thật được trong 24h:
| Thành phần | Khả thi 24h | Ghi chú |
|---|---|---|
| Đọc bài, phân loại, sinh nội dung, kiểm tuân thủ, dựng ảnh Pin | Có | Chạy trên dữ liệu thật của doppelherz.vn |
| Hàng chờ duyệt + lịch + log (Google Sheet hoặc trang HTML) | Có | |
| Đăng Facebook | Có, lên Page thử nghiệm của bạn | Page do chính tài khoản developer quản lý thì không cần App Review [Nguồn] |
| Đăng Pinterest | Một phần | App mới chỉ có Trial access: Pin tạo ra chỉ người tạo thấy; Standard access cần video demo và thời gian xét [Nguồn]. Demo: gọi API ở chế độ Trial nếu kịp được cấp, nếu không thì xuất gói Pin (ảnh + tiêu đề + mô tả + link) sẵn sàng đăng. Nói rõ điều này trong bài nộp. |

Phương án đối chứng nên nêu để thể hiện tư duy tư vấn: Pinterest có tính năng tự tạo Pin từ RSS của website [Chưa kiểm]. Chi phí gần bằng 0 nhưng không viết lại nội dung, không có ảnh dọc, không lọc và không duyệt – tức không giải được rủi ro tuân thủ. Đây là lý do dự án đáng 40–50 triệu.

Phân bổ thời gian gợi ý (tính từ lúc bắt đầu làm): 0–2h tạo tài khoản/khóa API; 2–8h lõi Python; 8–12h bọc bằng n8n + bảng duyệt + đăng Facebook; 12–18h tài liệu (Câu 1, chẩn đoán, kế hoạch, báo giá, sơ đồ, bộ prompt); 18–22h quay video demo, đóng gói; 2h dự phòng.

## 4. Lưu ý ngành thực phẩm chức năng

1. **Chỉ nói điều đã được xác nhận.** TPBVSK phải đăng ký và được xác nhận nội dung quảng cáo trước khi quảng cáo; nội dung phải khớp công dụng đã công bố [Nguồn]. Hệ quả: AI không được tự viết công dụng – chỉ lắp ghép từ sổ XNQC.
2. **Cụm từ bắt buộc:** "Thực phẩm bảo vệ sức khỏe" và câu khuyến cáo không phải là thuốc; chữ rõ, tương phản nền [Nguồn].
3. **Không dùng hình ảnh, trang phục, tên của bác sĩ, dược sĩ, cơ sở y tế, thư cảm ơn của người bệnh** để quảng cáo thực phẩm; mức phạt báo chí nêu 20–30 triệu [Nguồn]. Hệ quả: prompt sinh ảnh cấm áo blouse/bệnh viện; không dùng lời chứng thực; bài sự kiện có dược sĩ/viện (ví dụ bài 16/10/2026 về đào tạo dược sĩ) phải qua pháp chế trước khi lên social.
4. **Từ ngữ bệnh lý:** chặn "chữa", "điều trị", "đặc trị", "khỏi hẳn", "dứt điểm", "100%", cam kết thời gian. Dùng "hỗ trợ", "bổ sung".
5. **So sánh nhất:** "số 1", "tốt nhất" cần tài liệu chứng minh [Chưa kiểm – Luật Quảng cáo Điều 8]. Website đang dùng "số 1 tại Đức" → hỏi khách hàng tài liệu gốc và phạm vi được dùng.
6. **Ranh giới kiến thức – quảng cáo:** bài kiến thức trở thành quảng cáo khi nêu tên sản phẩm kèm công dụng. Vì vậy cần phân loại E/P ngay từ đầu.
7. **Nhóm nhạy cảm:** sản phẩm cho trẻ em và phụ nữ mang thai → luôn hai lớp duyệt.
8. **Chính sách nền tảng** [Nguồn thứ cấp]: Pinterest cấm ngôn ngữ/hình ảnh giảm cân trong quảng cáo, cấm before–after và lời chứng thực; Meta cấm nội dung quảng cáo ngụ ý biết tình trạng sức khỏe người xem, và từ 07/2026 xét theo câu chữ tuyên bố thay vì theo loại sản phẩm. Viết bài thường theo chuẩn "chạy quảng cáo được" để khi boost không bị từ chối.
9. **Căn cứ pháp lý đang thay đổi:** Luật Quảng cáo sửa đổi hiệu lực 01/01/2026, NĐ 342/2025 hiệu lực 15/02/2026; NĐ 46/2026 thay NĐ 15/2018 nhưng có nguồn ghi đang tạm ngưng; thẩm quyền cấp XNQC chuyển về UBND cấp tỉnh từ 01/07/2026 [Nguồn]. Hệ quả: bộ luật kiểm tra phải là file cấu hình do pháp chế duyệt, không viết cứng trong code. Phần này không thay cho ý kiến pháp chế.
10. **AI và nội dung y tế:** chỉ viết lại từ bài gốc; cấm thêm số liệu, nghiên cứu, liều dùng. Bài gốc hiện không có tài liệu tham khảo nên AI cũng không có gì để đối chiếu – nên nêu thành khuyến nghị.
11. **Bình luận/inbox:** người dùng sẽ hỏi chuyện bệnh. Ngoài phạm vi dự án đầu nhưng phải có quy tắc: không tư vấn điều trị, chuyển hotline.

## 5. Giới hạn
- Chưa đo Facebook VN. Chưa xác minh doanh nghiệp có tài khoản Pinterest. Các trang chi tiết Pin Đức không đọc được nên không có số lượt lưu/nhấp.
- Mẫu website là 40 bài mới nhất một danh mục, không đại diện cho toàn bộ 2.125 bài.
- Nhận định bố cục Pin dựa trên 12 ảnh mới nhất.

## Nguồn
- doppelherz.vn REST API; de.pinterest.com/doppelherz_de (hồ sơ, RSS), i.pinimg.com – truy cập 2026-11-19
- Quy định: dantri.com.vn, vnexpress.net, vietnamnet.vn (NĐ 15/2018 Điều 27); luatvietnam.vn, thuvienphapluat.vn (NĐ 46/2026); baodautu.vn (phân cấp XNQC từ 01/07/2026); tuoitre.vn/plo (NĐ 342/2025); mwyconsulting.com, ltvlaw.com (tổng hợp 2026)
- Nền tảng: developers.pinterest.com (access tiers); policy.pinterest.com; các bài tổng hợp chính sách Meta/Pinterest 2026
