# 2.1 Chẩn đoán hiện trạng – Dự án đồng bộ nội dung Website → Facebook + Pinterest

**Khách hàng:** Doppelherz Việt Nam (CTCP Mastertran) · **Vai trò của chúng tôi:** đối tác tư vấn và triển khai · **Ngày lập:** 19/11/2026

**Nhãn dùng trong tài liệu:**
- **[Đo]**: số liệu tự đo từ website công khai doppelherz.vn ngày 19/11/2026.
- **[Nguồn]**: lấy từ tài liệu, báo chí hoặc trang của nền tảng (xem mục Nguồn).
- **[Suy luận]**: suy ra từ dữ kiện, chưa được khách xác nhận.
- **[Chưa xác minh]**: chưa kiểm được, phải hỏi hoặc thử.

Tài liệu gồm ba phần:
- **Phần A.** Những gì quan sát được từ bên ngoài, trước buổi khảo sát. Mỗi điểm kèm hệ quả với giải pháp.
- **Phần B.** Bộ câu hỏi khảo sát, nhóm theo chủ đề. Mỗi câu nêu vì sao hỏi và câu trả lời làm thiết kế thay đổi ra sao.
- **Phần C.** Tài liệu và quyền truy cập cần khách cung cấp, và cách tổ chức buổi khảo sát.

---

## Phần A – Quan sát từ bên ngoài

### A1. Kết luận sơ bộ

Doppelherz không thiếu nội dung. Website có 2.125 bài viết [Đo]. Vấn đề là **thiếu một dây chuyền đưa nội dung ra mạng xã hội đều đặn và có kiểm soát**:
- Bài lên website theo đợt, không theo lịch.
- Website trộn bài cho người tiêu dùng với thông báo cho đại lý.
- Ngành thực phẩm bảo vệ sức khỏe (TPBVSK) bị ràng buộc chặt về quảng cáo. Một lỗi có thể bị phạt ngang ngân sách cả dự án.

Vì vậy, giải pháp đề xuất **không phải "đăng tự động hoàn toàn"**. Giải pháp là: tự động phần viết, dựng ảnh, xếp lịch, báo cáo. Con người giữ bước duyệt. Đây là giả thuyết. Buổi khảo sát dùng để xác nhận hoặc bác bỏ nó.

### A2. Dữ kiện và hệ quả

| # | Quan sát | Bằng chứng | Hệ quả với giải pháp |
|---|---|---|---|
| 1 | **Đăng theo đợt, không theo lịch** | 219 bài trong 12 tháng (11/2025–10/2026). Chỉ 66/342 ngày có bài mới. Khoảng trống dài nhất 39 ngày. Nhiều ngày đăng 9–10 bài cùng lúc. Tháng nhiều nhất 47 bài (04/2026), có tháng 1–2 bài [Đo] | Không thể "có bài là đăng ngay". Cần **hàng đợi + khung giờ đăng rải đều**. Hệ thống phải chịu được đợt 10 bài/ngày |
| 2 | **Website trộn nội dung người tiêu dùng với thông báo đại lý** | Ngày 25/08/2026 có 46 "bài viết" là file PDF thông báo chương trình bán hàng (ví dụ "Chương trình cân bằng kho ETC"), nằm trong danh mục "Chương trình khuyến mại" [Đo]. Danh mục "Tin nội bộ" có 136 bài [Đo]. Các thông báo này có vẻ dành cho đại lý [Suy luận] | Đồng bộ "mù" sẽ đẩy chính sách đại lý lên fanpage. Bắt buộc **bộ lọc theo loại nội dung** và **bước duyệt** |
| 3 | **Ràng buộc quảng cáo TPBVSK** | 36/36 trang sản phẩm có câu khuyến cáo "không phải là thuốc"; 34/36 ghi số xác nhận nội dung quảng cáo (XNQC) [Đo]. Mức phạt báo chí nêu cho một lỗi quảng cáo: 20–40 triệu đồng [Nguồn]. Căn cứ pháp lý đang thay đổi trong 2026 (Luật Quảng cáo sửa đổi, NĐ 342/2025) [Nguồn] | AI **không được tự viết công dụng**. Bài sản phẩm chỉ dùng công dụng trong sổ XNQC. Cần **cổng tuân thủ** (luật cứng + rà soát) và **người duyệt**. Bộ luật phải là file cấu hình do người phụ trách pháp lý quảng cáo (khách chỉ định) [Giả định] sửa được |
| 4 | **Website đã có sẵn "sổ công dụng được phép nói"** | Trang sản phẩm ghi số XNQC, số đăng ký và 2–3 dòng công dụng [Đo] | Cổng tuân thủ có dữ liệu để đối chiếu ngay. Không phải xây sổ mới, chỉ cần người phụ trách pháp lý xác nhận bản đang dùng |
| 5 | **Pinterest chưa hiện diện** | Website không có liên kết Pinterest, không cài Pinterest tag (có GTM và Facebook Pixel) [Đo]. Kênh Doppelherz Đức nhỏ (358 người theo dõi) và ngừng đăng từ 07/2025 [Đo] | Pinterest là **kênh phụ, chi phí thấp**. Đặt KPI khiêm tốn. Chưa biết khách đã có tài khoản Pinterest chưa [Chưa xác minh] |
| 6 | **Pinterest API có rào cản thời gian** | App mới chỉ được Trial access: Pin chỉ người tạo thấy. Muốn công khai phải xin Standard access kèm video demo [Nguồn]. Chờ cấp Trial cũng mất 1–4 tuần theo người dùng diễn đàn [Nguồn] | Phải đưa thời gian chờ vào kế hoạch. Bản demo đã có đường thay thế qua Make.com, Pin công khai |
| 7 | **Ảnh đại diện là ảnh ngang** | Ảnh phổ biến nhất 600×400 (19/40 bài mẫu), tỉ lệ 3:2 [Đo]. 199/2.125 bài không có ảnh đại diện [Đo] | Pinterest ưu tiên ảnh dọc 2:3 [Suy luận, theo kênh Đức 12/12 Pin là 2:3]. Cần **dựng ảnh Pin dọc theo mẫu**. Bài không ảnh thì bỏ qua Pinterest |
| 8 | **Không đo được kênh nào mang về đơn** | 0/40 bài có tham số UTM. 40/40 bài dùng link rút gọn bom.so [Đo] | Mọi link hệ thống đăng phải gắn **UTM theo kênh**. Cần hỏi bom.so có giữ được UTM không |
| 9 | **Kho 2.125 bài cũ chưa được dùng lại** | 1.300 bài thuộc "Kiến thức sức khỏe" [Đo] | Lợi ích lớn nhất của Pinterest có thể đến từ bài cũ, không chỉ bài mới. Để ở giai đoạn sau, nhưng thiết kế phải cho phép |
| 10 | **Phụ thuộc một tài khoản đăng bài** | Một tài khoản tác giả đứng tên 1.795/2.125 bài (84,5%) [Đo] | Nếu đây là tài khoản của một người [Suy luận – hỏi ở B3.5], sẽ có rủi ro khi người đó vắng. Cần phân quyền rõ, có người dự phòng, đào tạo ít nhất 2 người |
| 11 | **Fanpage có thể lớn** | Khoảng 628 nghìn người theo dõi [Chưa xác minh – kết quả tìm kiếm]. Chưa đọc được nội dung, tần suất, tương tác của fanpage [Chưa xác minh] | Một bài sai sẽ lan nhanh. Củng cố lý do phải có người duyệt. Cần khách xuất 20–30 bài gần nhất để phân tích giọng văn |
| 12 | **Website dễ tích hợp** | WordPress 6.7.11, theme Flatsome, WooCommerce, Rank Math SEO, chạy sau Cloudflare. REST API và RSS mở công khai [Đo] | Tích hợp bằng plugin mỏng, không sửa giao diện. Cloudflare có thể chặn Facebook/Pinterest tải ảnh [Suy luận] → kiểm tra khi thử |
| 13 | **Bài gốc không có tài liệu tham khảo** | 0/40 bài mẫu có mục tài liệu tham khảo [Đo] | AI không có gì để đối chiếu số liệu. Quy tắc: AI chỉ viết lại, không thêm số liệu. Khuyến nghị khách bổ sung nguồn trong bài mới |
| 14 | **Có bài tiếng Anh** | 17 bài thuộc danh mục tiếng Anh trong 12 tháng [Đo] | Bản đầu bỏ qua bài tiếng Anh. Hỏi khách có cần không |
| 15 | **Dùng cụm so sánh nhất** | Website dùng cụm "số 1 tại Đức" [Đo] | Cụm so sánh nhất cần tài liệu chứng minh [Chưa xác minh – Luật Quảng cáo]. Mặc định chặn, trừ khi khách cung cấp tài liệu và người phụ trách pháp lý cho phép |

### A3. Phân loại nội dung đề xuất (để thảo luận trong buổi khảo sát)

| Loại | Dấu hiệu | Facebook | Pinterest | Mức duyệt |
|---|---|---|---|---|
| E – Kiến thức | Danh mục Kiến thức sức khỏe và các danh mục con | Có | Có (ưu tiên) | 1 người duyệt |
| P – Sản phẩm | Bài/trang có tên sản phẩm + công dụng | Có | Hạn chế | Marketing + người phụ trách pháp lý; chỉ dùng công dụng trong sổ XNQC |
| N – Tin thương hiệu | Sự kiện, trách nhiệm xã hội, báo chí | Có | Không | 1 người duyệt; bài có hình ảnh/danh nghĩa nhân viên y tế → người phụ trách pháp lý |
| X – Không đồng bộ | Thông báo cho đại lý (PDF), Tin nội bộ | Không | Không | Chặn tự động |

Sản phẩm cho trẻ em và phụ nữ mang thai: luôn hai lớp duyệt [Suy luận – đề xuất thận trọng, chờ người phụ trách pháp lý quyết].

### A4. Bản demo đã chứng minh gì, chưa chứng minh gì

Chúng tôi đã dựng bản demo chạy thật trên website thử nghiệm https://shop.adaptivealgo.uk và n8n https://n8n.adaptivealgo.uk.

| Đã chạy và đo được | Chưa có trong demo (là phạm vi dự án thật) |
|---|---|
| Plugin WordPress bắt sự kiện xuất bản, chống đăng trùng, gửi nền (nút Đăng không phải chờ) | Tra sổ XNQC cho bài sản phẩm (công dụng nguyên văn) |
| Claude phân loại E/P/N/X và viết nội dung riêng cho Facebook, Pinterest trong 1 lần gọi; kiểm tra độ dài và cụm từ cấm; không đạt thì dùng mẫu | Lớp AI rà soát tuân thủ thứ hai; bộ luật do người phụ trách pháp lý duyệt |
| Chặn bài theo danh mục và thông báo đại lý dạng PDF; câu khuyến cáo do code gắn cho bài sản phẩm | Hai lớp duyệt cho nhóm nhạy cảm (hiện chỉ gắn cờ cảnh báo) |
| **Duyệt trước khi đăng**: xem, sửa nội dung, chọn giờ, duyệt hoặc từ chối; chống hai người duyệt trùng | Dựng ảnh Pin dọc 2:3 |
| Lịch đăng tự rải: khung giờ, số bài tối đa/ngày, khoảng cách tối thiểu; giờ chọn tay cho từng bài | Lịch nội dung trên Google Sheet; lượt truy cập theo UTM qua GA4 |
| Facebook lên bài sau ~7 giây; Pinterest (qua Make.com) đúng giờ đặt, Pin công khai | Báo cáo tuần đã đọc được tương tác Facebook; chưa đọc được số liệu Pin (cần API Pinterest riêng) |
| Nhật ký, luồng báo lỗi, trang Báo cáo + báo cáo tuần do Claude tóm tắt; chạy thử trên bài thật của doppelherz.vn (không đăng) | Số liệu Pin (lượt xem, lưu): cần API Pinterest riêng |

Ý nghĩa: hai rủi ro kỹ thuật lớn nhất (đăng Facebook bằng API và tạo Pin công khai) đã được gỡ, và quy trình người duyệt đã chạy được. Dự án thật tập trung vào phần **tuân thủ theo dữ liệu của khách** (sổ XNQC, bộ luật đã được người phụ trách pháp lý duyệt) và **đưa lên website thật**.

---

## Phần B – Bộ câu hỏi khảo sát

Cách đọc bảng:
- **Ưu tiên 1**: phải có câu trả lời trong buổi khảo sát đầu, vì ảnh hưởng phạm vi hoặc báo giá.
- **Ưu tiên 2**: hỏi được qua email/Zalo trong tuần thiết kế.

### B1. Mục tiêu kinh doanh và KPI

| # | Câu hỏi | Ưu tiên | Vì sao hỏi | Câu trả lời thay đổi thiết kế thế nào |
|---|---|---|---|---|
| 1.1 | Anh/chị muốn dự án này giải quyết việc gì nhất: tiết kiệm công đăng bài, đăng đều hơn, tăng lượt truy cập website, hay mở kênh Pinterest? | 1 | Mỗi mục tiêu dẫn tới một tiêu chí nghiệm thu khác | Nếu là "tiết kiệm công" → đo thời gian/bài trước–sau. Nếu là "lượt truy cập" → bắt buộc UTM + báo cáo đọc GA4 |
| 1.2 | Hiện mỗi tháng đăng bao nhiêu bài Facebook? Bao nhiêu bài đến từ website, bao nhiêu là nội dung riêng của fanpage? | 1 | Website chỉ có ~18 bài/tháng trung bình [Đo]. Fanpage có thể đăng nhiều hơn | Quyết định hệ thống chỉ lo bài từ website, hay phải nhận cả nội dung không có trên website |
| 1.3 | Kỳ vọng tần suất đăng mong muốn trên Facebook và Pinterest là bao nhiêu bài/tuần? | 1 | Quyết định cấu hình hàng đợi và chi phí AI | Nếu muốn đều hằng ngày mà website chỉ có đợt → cần lấy thêm bài cũ (giai đoạn 2) |
| 1.4 | Pinterest có nằm trong kế hoạch marketing chính thức không, hay là kênh thử? | 1 | Kênh Đức nhỏ và đã ngừng [Đo]. Không nên hứa hiệu quả lớn | Nếu là thử → giữ chi phí Pinterest ở mức 0đ (Make Free), KPI khiêm tốn |
| 1.5 | Sau 3 tháng, con số nào sẽ khiến anh/chị nói "dự án thành công"? | 1 | Cần tiêu chí đo được, đã thống nhất từ đầu | Đưa vào biên bản nghiệm thu |
| 1.6 | Có kế hoạch chạy quảng cáo trả phí (boost) trên các bài này không? | 2 | Chính sách quảng cáo của Meta chặt hơn bài thường [Nguồn] | Nếu có → viết theo chuẩn "chạy quảng cáo được" ngay từ đầu |

### B2. Quy trình nội dung hiện tại

| # | Câu hỏi | Ưu tiên | Vì sao hỏi | Câu trả lời thay đổi thiết kế thế nào |
|---|---|---|---|---|
| 2.1 | Một bài từ lúc lên ý tưởng đến lúc lên fanpage đi qua những bước nào, ai làm, mất bao lâu? | 1 | Cần sơ đồ quy trình hiện tại để biết tự động bước nào | Chọn điểm "bắt" bài: lúc xuất bản trên website, hay sớm hơn (bài nháp) |
| 2.2 | Vì sao bài lên website theo đợt (có ngày 9–10 bài)? Do lịch biên tập, do agency giao theo lô, hay do đăng bù? | 1 | Giải thích dữ kiện A2-1 | Nếu do agency giao theo lô → hàng đợi rải đều là giá trị chính của dự án |
| 2.3 | Ai viết bài website: nội bộ hay agency? Ai viết caption fanpage? | 1 | Xác định ai là người dùng hệ thống | Nếu agency → cần tài khoản riêng và quyền hạn chế cho agency |
| 2.4 | Fanpage hiện có giọng văn, mẫu caption, bộ hashtag cố định không? | 2 | Prompt phải học đúng giọng thương hiệu | Đưa mẫu vào prompt; 20–30 bài gần nhất làm bộ bài mẫu đánh giá |
| 2.5 | Thông báo chương trình cho đại lý (file PDF) có cố ý để công khai trên website không? | 1 | Dữ kiện A2-2 | Xác nhận luật chặn loại X. Có thể là một phát hiện phụ có giá trị cho khách |
| 2.6 | Khi sửa bài website sau khi đã đăng, có cần sửa bài trên fanpage không? | 2 | Demo chỉ đồng bộ lần xuất bản đầu | Nếu cần → thêm cảnh báo "bài gốc đã sửa" cho người duyệt; không tự sửa bài trên mạng xã hội |
| 2.7 | Có đăng nội dung cho dịp lễ, chiến dịch theo lịch cố định không? | 2 | Ảnh hưởng lịch nội dung | Thêm cột "chiến dịch" trong lịch nội dung |

### B3. Con người và phân quyền

| # | Câu hỏi | Ưu tiên | Vì sao hỏi | Câu trả lời thay đổi thiết kế thế nào |
|---|---|---|---|---|
| 3.1 | Ai là đầu mối dự án phía Doppelherz, có quyền quyết định phạm vi và nghiệm thu? | 1 | Tránh dự án chờ quyết định | Ghi vào RACI và lịch họp tuần |
| 3.2 | Ai sẽ duyệt bài trước khi đăng? Có người dự phòng khi người đó vắng không? | 1 | Bước duyệt là điểm nghẽn dễ xảy ra nhất | Nếu chỉ một người → thêm người dự phòng và nhắc việc tự động khi bài chờ quá hạn |
| 3.3 | Người duyệt muốn duyệt ở đâu: trong WordPress, qua email, hay Zalo/Telegram? | 1 | Quyết định giao diện duyệt | WordPress là mặc định (đã có trang nhật ký). Kênh nhắc việc chọn theo thói quen của người duyệt |
| 3.4 | Thời gian chấp nhận được từ lúc bài lên website đến lúc lên fanpage là bao lâu? | 1 | Đặt cam kết thời gian duyệt (SLA) | Nếu cần trong ngày → khung giờ đăng phải chừa thời gian duyệt |
| 3.5 | Tài khoản tác giả đứng tên 84,5% bài [Đo] là của một người hay tài khoản dùng chung? | 2 | Rủi ro phụ thuộc một người, và an toàn tài khoản | Nếu dùng chung → đề xuất tách tài khoản theo người để nhật ký ghi đúng ai làm gì |
| 3.6 | Ai sẽ vận hành hệ thống sau bàn giao (xem lỗi, đồng bộ lại)? | 1 | Quyết định nội dung đào tạo và gói bảo trì | Đào tạo ít nhất 2 người phía khách |

### B4. Tuân thủ và pháp lý quảng cáo

| # | Câu hỏi | Ưu tiên | Vì sao hỏi | Câu trả lời thay đổi thiết kế thế nào |
|---|---|---|---|---|
| 4.0 | Hiện ai duyệt pháp lý cho nội dung quảng cáo: pháp chế nội bộ, luật sư/đơn vị thuê ngoài, hay Marketing tự duyệt? | 1 | Chưa biết khách có bộ phận pháp chế hay không [Chưa xác minh] | Xác định người phụ trách pháp lý trong luồng duyệt và bảng RACI |
| 4.1 | Hiện bài fanpage có qua người duyệt pháp lý không? Loại bài nào bắt buộc qua người duyệt pháp lý? | 1 | Quyết định số lớp duyệt cho từng loại E/P/N | Ánh xạ thẳng vào luồng duyệt |
| 4.2 | Người duyệt pháp lý có danh sách từ ngữ cấm/được phép dùng không? | 1 | Cổng tuân thủ cần danh sách gốc | Thay danh sách khởi đầu của chúng tôi bằng danh sách của người phụ trách pháp lý |
| 4.3 | Sổ XNQC trên website có phải bản mới nhất không? Ai cập nhật khi có số XNQC mới? | 1 | Sổ là nguồn duy nhất cho công dụng sản phẩm | Nếu website chưa cập nhật → cần nguồn khác (bảng theo dõi XNQC của người phụ trách hồ sơ quảng cáo, nếu có [Chưa xác minh]) |
| 4.4 | Bài kiến thức (không nêu sản phẩm) có bị coi là quảng cáo khi đăng trên fanpage thương hiệu không? | 1 | Ranh giới kiến thức – quảng cáo quyết định mức duyệt loại E | Nếu có → loại E cũng phải kèm câu khuyến cáo và qua người phụ trách pháp lý |
| 4.5 | Cụm "số 1 tại Đức" có tài liệu chứng minh và được phép dùng trên mạng xã hội không? | 2 | Dữ kiện A2-15 | Quyết định chặn hay cho phép cụm so sánh nhất |
| 4.6 | Bài có hình ảnh bác sĩ, dược sĩ, sự kiện đào tạo nhân viên y tế xử lý thế nào? | 2 | Quy định cấm dùng danh nghĩa nhân viên y tế để quảng cáo thực phẩm [Nguồn] | Thêm luật: bài loại N có từ khóa y tế → luôn qua người phụ trách pháp lý |
| 4.7 | Doanh nghiệp có quy định nội bộ về việc dùng AI tạo nội dung không? Có cần ghi chú "nội dung có hỗ trợ AI" không? | 2 | Quy định và chính sách nền tảng về nội dung AI có thể thay đổi [Chưa xác minh] | Thêm/không thêm dòng ghi chú; lưu nhật ký AI để kiểm tra lại |

### B5. Tài khoản và quyền truy cập (Facebook, Pinterest, Meta Business)

| # | Câu hỏi | Ưu tiên | Vì sao hỏi | Câu trả lời thay đổi thiết kế thế nào |
|---|---|---|---|---|
| 5.1 | Fanpage thuộc Meta Business Portfolio nào? Ai là quản trị viên đầy đủ quyền? | 1 | Cần người có quyền tạo app và cấp token | Nếu fanpage do agency giữ → phải chuyển quyền trước, tốn thêm thời gian |
| 5.2 | Doanh nghiệp đã xác minh doanh nghiệp (Business Verification) trên Meta chưa? | 1 | App do chính admin Page của khách tạo thì không cần App Review [Nguồn]. Nếu phải dùng app khác → cần App Review + xác minh, 1–3 tuần [Nguồn] | Chọn phương án tạo app trong Business của khách. Nếu chưa xác minh và cần → đưa vào đường găng kế hoạch |
| 5.3 | Có tài khoản Pinterest doanh nghiệp chưa? Đã xác minh tên miền doppelherz.vn chưa? | 1 | Website không có liên kết Pinterest [Đo] | Nếu chưa có → tạo mới trong tuần 1, thêm thẻ xác minh lên website |
| 5.4 | Anh/chị chấp nhận dùng Make.com làm cầu nối Pinterest trong lúc chờ Pinterest duyệt API không? | 1 | Demo đã chạy được qua Make, Pin công khai | Nếu không → Pinterest chỉ chạy sau khi được Standard access, không cam kết được ngày |
| 5.5 | Tài khoản dịch vụ (VPS, Claude API, Make) đứng tên ai, thanh toán bằng thẻ nào? | 1 | Chúng tôi khuyến nghị mọi tài khoản đứng tên khách | Quyết định dòng chi phí nào khách tự trả trực tiếp |
| 5.6 | Có kênh nào khác cần làm tiếp sau (Instagram, Zalo OA, YouTube)? | 2 | Website liên kết cả bốn kênh [Đo] | Thiết kế luồng n8n tách khối theo kênh để thêm kênh sau rẻ hơn |

### B6. Kỹ thuật website và hạ tầng

| # | Câu hỏi | Ưu tiên | Vì sao hỏi | Câu trả lời thay đổi thiết kế thế nào |
|---|---|---|---|---|
| 6.1 | Website đang host ở đâu, ai quản trị? Có môi trường thử (staging) không? | 1 | Không cài plugin thẳng lên website thật khi chưa thử | Nếu không có staging → chúng tôi dựng bản sao tạm, cần thêm thời gian |
| 6.2 | Có quy định về việc cài plugin mới, hay đơn vị nào đang bảo trì website (để không xung đột)? | 1 | Tránh plugin bị gỡ khi bảo trì | Thống nhất quy trình cập nhật với đơn vị bảo trì |
| 6.3 | Cloudflare có bật chặn bot/tường lửa riêng không? | 2 | Facebook/Pinterest cần tải ảnh từ website [Suy luận] | Thêm luật cho phép bot của Meta/Pinterest |
| 6.4 | Doanh nghiệp có chính sách đặt dữ liệu trên máy chủ trong nước hoặc trên hạ tầng công ty không? | 1 | Quyết định chỗ đặt n8n | Mặc định: VPS trong nước đứng tên khách. Nếu có máy chủ nội bộ → dùng máy đó, bỏ chi phí VPS |
| 6.5 | Có thể tạo tên miền phụ (ví dụ n8n.doppelherz.vn) cho hệ thống không? Ai quản lý DNS? | 2 | Để truy cập n8n có HTTPS | Nếu không → dùng tên miền khác, phát sinh chi phí nhỏ |
| 6.6 | Link rút gọn bom.so là công cụ gì, có giữ tham số UTM không? | 2 | Dữ kiện A2-8 | Quyết định gắn UTM vào link gốc hay link rút gọn |

### B7. Dữ liệu và đo lường

| # | Câu hỏi | Ưu tiên | Vì sao hỏi | Câu trả lời thay đổi thiết kế thế nào |
|---|---|---|---|---|
| 7.1 | Website có Google Analytics 4 không? Ai có quyền xem? | 1 | Website có GTM [Đo], chưa rõ có GA4 | Có GA4 → báo cáo tuần đọc được lượt truy cập theo UTM. Không có → báo cáo chỉ đếm bài |
| 7.2 | Hiện báo cáo marketing gửi cho ai, theo chu kỳ nào, dạng gì? | 2 | Báo cáo của hệ thống nên nhập vào báo cáo có sẵn | Chọn định dạng (Google Sheet, email) và ngày gửi |
| 7.3 | Có muốn đo tương tác (lượt thích, tiếp cận) trong báo cáo không? | 2 | Đọc số liệu Page cần thêm quyền và thêm công | Bản đầu: không. Đưa vào giai đoạn sau nếu cần |
| 7.4 | Số liệu Facebook 20–30 bài gần nhất có xuất được không? | 2 | Chưa đọc được fanpage từ bên ngoài [Chưa xác minh] | Làm bộ bài mẫu đánh giá chất lượng AI |

### B8. Ngân sách và vận hành sau bàn giao

| # | Câu hỏi | Ưu tiên | Vì sao hỏi | Câu trả lời thay đổi thiết kế thế nào |
|---|---|---|---|---|
| 8.1 | Khung 40–50 triệu đã gồm VAT và chi phí vận hành năm đầu chưa? | 1 | Ảnh hưởng trực tiếp báo giá | Chọn phương án tối thiểu hay đề xuất |
| 8.2 | Doanh nghiệp thanh toán dịch vụ nước ngoài (Claude API, Make) bằng thẻ công ty được không? | 1 | Các dịch vụ này thu bằng USD | Nếu không → đối tác trả hộ và thu lại, cần điều khoản riêng |
| 8.3 | Sau bàn giao, muốn tự vận hành hay thuê gói bảo trì? | 1 | Quyết định dòng bảo trì trong báo giá | Tự vận hành → đào tạo sâu hơn, bỏ dòng bảo trì |
| 8.4 | Ai phê duyệt chi phí phát sinh (ví dụ nâng gói Make, tăng số bài)? | 2 | Tránh hệ thống dừng vì hết hạn mức mà không ai duyệt | Đặt ngưỡng cảnh báo chi phí và người nhận cảnh báo |

### B9. Rủi ro

| # | Câu hỏi | Ưu tiên | Vì sao hỏi | Câu trả lời thay đổi thiết kế thế nào |
|---|---|---|---|---|
| 9.1 | Đã từng bị nhắc nhở hoặc phạt về nội dung quảng cáo chưa? Bài nào, vì sao? | 1 | Biết lỗi thật để đưa vào bộ luật | Thêm luật chặn cụ thể |
| 9.2 | Nếu một bài sai đã lên fanpage, quy trình xử lý hiện tại là gì? | 2 | Hệ thống cần hỗ trợ gỡ nhanh | Thêm hướng dẫn gỡ bài và ghi nhận sự cố |
| 9.3 | Có giai đoạn nào trong năm không được thay đổi hệ thống (cao điểm bán hàng, Tết)? | 1 | Lịch triển khai rơi vào tháng 12–01 | Đặt mốc chạy song song tránh cao điểm |
| 9.4 | Có ràng buộc bảo mật nào khi đối tác truy cập website và fanpage không (NDA, tài khoản riêng)? | 1 | Cần quyền admin tạm thời | Ký NDA; dùng tài khoản riêng, thu hồi sau bàn giao |

---

## Phần C – Tài liệu, quyền truy cập và cách tổ chức khảo sát

### C1. Tài liệu và quyền truy cập cần khách cung cấp

| # | Hạng mục | Dùng để | Cần trước |
|---|---|---|---|
| 1 | Tài khoản quản trị website trên bản thử (staging), hoặc quyền tạo bản sao | Cài và thử plugin | Tuần 2 |
| 2 | Quyền quản trị fanpage trong Meta Business của khách (hoặc người phía khách thao tác cùng) | Tạo app Meta, lấy token đăng bài | Tuần 2 |
| 3 | Tài khoản Pinterest doanh nghiệp (hoặc đồng ý tạo mới) và quyền thêm thẻ xác minh lên website | Kết nối Pinterest, xác minh tên miền | Tuần 1 |
| 4 | Danh sách từ ngữ cấm/được phép và quy định duyệt nội dung hiện có (nếu có) của người phụ trách pháp lý | Bộ luật của cổng tuân thủ | Tuần 2 |
| 5 | Bảng sản phẩm và số XNQC chính thức (nếu khác website) | Sổ công dụng được phép nói | Tuần 2 |
| 6 | Hướng dẫn nhận diện thương hiệu: logo, màu, font, ảnh được phép dùng | Mẫu ảnh Pin | Tuần 2 |
| 7 | 20–30 bài fanpage gần nhất (xuất từ Meta Business Suite hoặc chụp màn hình) | Học giọng văn, bộ bài đánh giá | Tuần 1 |
| 8 | Quyền xem GA4 (nếu có) | Báo cáo theo UTM | Tuần 3 |
| 9 | Quyền tạo bản ghi DNS (tên miền phụ cho n8n) hoặc bên quản trị website/tên miền (nội bộ hoặc thuê ngoài) [Chưa xác minh] làm cùng | Truy cập n8n qua HTTPS | Tuần 3 |
| 10 | Tài khoản thanh toán cho VPS, Claude API, Make (đứng tên khách) | Khách sở hữu tài khoản dịch vụ | Tuần 3 |
| 11 | Danh sách người duyệt và người dự phòng | Phân quyền, nhắc việc | Tuần 2 |
| 12 | Thỏa thuận bảo mật (NDA) | Trước khi nhận quyền truy cập | Trước tuần 1 |

### C2. Cách tiến hành buổi khảo sát

**Hình thức:** một buổi chính 120 phút tại văn phòng khách, cộng 30–45 phút quan sát người đăng bài làm việc thật. Câu hỏi ưu tiên 2 gửi qua email sau buổi.

**Người tham dự:**

| Phía | Người | Tham dự phần |
|---|---|---|
| Doppelherz | Đầu mối marketing (người ra quyết định) | Toàn buổi |
| Doppelherz | Người đang đăng bài website/fanpage | Phần quy trình + quan sát |
| Doppelherz | Người phụ trách pháp lý quảng cáo (khách chỉ định) [Giả định] | Phần tuân thủ (30 phút) |
| Doppelherz | Bên quản trị website (nội bộ hoặc thuê ngoài) [Chưa xác minh] | Phần kỹ thuật (20 phút) |
| Đối tác | PM/Tư vấn giải pháp | Toàn buổi, dẫn buổi |
| Đối tác | Kỹ sư tự động hóa | Phần kỹ thuật và tài khoản |

**Chương trình:**

| Thời lượng | Nội dung | Câu hỏi |
|---|---|---|
| 15 phút | Mục tiêu dự án, trình bày nhanh quan sát Phần A và chạy demo trực tiếp | – |
| 25 phút | Mục tiêu, KPI, ngân sách | B1, B8 |
| 30 phút | Quy trình nội dung, con người, duyệt bài | B2, B3 |
| 30 phút | Tuân thủ với người phụ trách pháp lý; chốt sơ bộ bảng phân loại E/P/N/X | B4, B9 |
| 20 phút | Kỹ thuật, tài khoản, đo lường với bên quản trị website | B5, B6, B7 |
| 30–45 phút | Quan sát người đăng bài làm một bài thật từ đầu đến cuối, bấm giờ từng bước | – |

**Đầu ra (gửi trong 2 ngày làm việc sau buổi):**
1. Biên bản khảo sát: câu trả lời, quyết định đã chốt, câu hỏi còn mở và người trả lời.
2. Sơ đồ quy trình hiện tại, có thời gian từng bước (làm mốc đo "trước – sau").
3. Bảng phân loại E/P/N/X và mức duyệt đã thống nhất.
4. Danh sách tài liệu/quyền truy cập còn thiếu, kèm hạn.
5. Phạm vi chốt và báo giá chính thức (chọn phương án tối thiểu hoặc đề xuất, xem tài liệu 2.3).

Công cho phần khảo sát đã tính trong kế hoạch (tài liệu 2.2) và báo giá (tài liệu 2.3).

---

## Nguồn

- Đề bài: `Bai_test_Quan_ly_du_an_AI.docx.md`, Câu 2.
- Khảo sát website doppelherz.vn qua REST API và RSS công khai, ngày 19/11/2026: `research/01_phan_tich_de_bai.md` (mục 2–4), `research/02_template_3_kenh_va_huong_prototype.md` (mục 1, 2.0, 4), `research/HANDOFF.md` (mục 3).
- Pinterest Doppelherz Đức: https://de.pinterest.com/doppelherz_de/ (truy cập 19/11/2026, theo `research/02_...` mục 1.3).
- Quy định quảng cáo TPBVSK (nguồn thứ cấp, không thay ý kiến pháp lý của khách): tuoitre.vn/plo – "Lưu ý về việc quảng cáo mỹ phẩm, thực phẩm từ 15-2-2026"; thuvienphapluat.vn – Luật 75/2025/QH15, NĐ 342/2025/NĐ-CP; dantri.com.vn, vnexpress.net, vietnamnet.vn (theo `research/02_...` mục Nguồn).
- Pinterest access tiers: https://developers.pinterest.com/docs/key-concepts/access-tiers ; thời gian chờ cấp quyền: community.pinterest.biz (theo `SocialSync-Brief.md` mục 8).
- Đăng bài lên Page qua Graph API: https://developers.facebook.com/docs/graph-api/reference/page/feed
- Số đo của bản demo: `nop-bai/04-so-do-quy-trinh.md` (Phần A), nhật ký chạy thử ngày 19/11/2026.
