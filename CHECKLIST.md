# ✅ CHECKLIST TRƯỚC KHI NỘP – DBS401 Group 02

## A. Checklist Chất Lượng Flag

| # | Kiểm tra | Flag 1 | Flag 2 | Flag 3 |
|---|---------|--------|--------|--------|
| 1 | Không thể lấy flag bằng 1 request đơn giản | ✅ | ✅ | ✅ |
| 2 | Không thấy flag khi SELECT * FROM FLAGS | ✅ Hex encoded | ✅ Không ở FLAGS | ✅ Không ở FLAGS |
| 3 | Flag không nằm trong HTML source | ✅ | ✅ | ✅ |
| 4 | Có ít nhất 3 bước khai thác/suy luận | ✅ 7 bước | ✅ 5 bước | ✅ 6 bước |
| 5 | Có fake data / decoy gây nhiễu | ✅ FL_DECOY_B + FLAG_ARCHIVE | ✅ TXN-004 fake | ✅ oracle_flag_3_backup |
| 6 | Có bước decode/transform | ✅ hex + reverse + base64 | ✅ base64 + reverse | ✅ hex decode suffix |
| 7 | Cần ghép nhiều mảnh từ nhiều bảng | ✅ 3 bảng | ✅ 1 nguồn | ✅ 2 nguồn (DB + manifest) |
| 8 | Có Answer Key đủ chi tiết | ✅ | ✅ | ✅ |
| 9 | Có secure fix rõ ràng | ✅ | ✅ | ✅ |
| 10 | 3 flag khó tương đương nhau | ✅ Very Hard | ✅ Very Hard | ✅ Very Hard | (Chained Attack) |
| 11 | Không flag nào dễ hơn đáng kể | ✅ | ✅ | ✅ |
| 12 | Không flag nào lộ ở client-side | ✅ | ✅ | ✅ |
| 13 | Fake flag hợp lý | ✅ 5 fake flags | ✅ | ✅ |
| 14 | Có hint gián tiếp | ✅ SYSTEM_HINTS | ✅ decode_hint in JSON | ✅ SYSTEM_HINTS |
| 15 | Có thể demo lại chắc chắn | ✅ ANSWER_KEY.md | ✅ | ✅ |
| 16 | Report ghi đúng Easy/Medium/Hard | ✅ Easy | ✅ Medium | ✅ Hard |
| 17 | Report tách riêng "độ khó lỗ hổng" và "độ khó tìm flag" | ✅ | ✅ | ✅ |

---

## B. Checklist Kỹ Thuật

| # | Kiểm tra | Trạng thái |
|---|---------|-----------|
| 1 | Oracle Database là DB chính | ✅ Oracle XE 21c / 23c Free |
| 2 | Web chạy local tại 127.0.0.1 | ✅ /dbs401-oracle-app |
| 3 | Truy cập được qua IP LAN | ✅ setup.sh in IP LAN |
| 4 | Source code đầy đủ | ✅ 15+ files |
| 5 | SQL đầy đủ (schema + seed) | ✅ schema.sql + seed.sql |
| 6 | setup.sh hỗ trợ copy local source | ✅ |
| 7 | setup.sh hỗ trợ git clone nếu có REPO_URL | ✅ |
| 8 | 3 lỗ hổng liên quan trực tiếp đến database | ✅ SQLi, Business Logic, Supply Chain |
| 9 | Có secure versions | ✅ secure_versions/ |
| 10 | Có script exploit Python (chỉ target local) | ✅ (Nếu có, cho Vuln 1 hoặc 3) |
| 11 | Script có cảnh báo lab only | ✅ |
| 12 | Không có backdoor/malware/reverse shell | ✅ |
| 13 | Không hướng dẫn tấn công hệ thống thật | ✅ |
| 14 | Session management cơ bản | ✅ |
| 15 | Login KHÔNG bị lỗ hổng (chỉ 3 endpoint được chọn) | ✅ login.php dùng bind var |

---

## C. Checklist Báo Cáo

| # | Kiểm tra | Trạng thái |
|---|---------|-----------|
| 1 | Trang bìa đầy đủ (môn, đề tài, nhóm, thành viên, ngày) | ⚠️ Điền thông tin thành viên |
| 2 | Mục lục | ✅ |
| 3 | Giới thiệu có cam kết lab nội bộ | ✅ |
| 4 | Bảng độ khó có cột "Mức độ lỗ hổng" và "Mức độ tìm flag" tách biệt | ✅ |
| 5 | Ghi chú giải thích Easy-Medium-Hard vs Very Hard | ✅ |
| 6 | Mỗi lỗ hổng có: mô tả, nguyên nhân, tác động, kịch bản, cách vá | ✅ |
| 7 | Hướng dẫn triển khai local rõ ràng | ✅ |
| 8 | Phân công công việc | ⚠️ Điền tên thành viên thực tế |
| 9 | Kết luận + tài liệu tham khảo | ✅ |
| 10 | Code snippets minh họa lỗi + cách vá | ✅ |

---

## D. Checklist Demo Trực Tiếp

| # | Bước demo | Cần chuẩn bị |
|---|----------|-------------|
| 1 | Giới thiệu web app (login, dashboard) | Tài khoản student1 sẵn sàng |
| 2 | Demo Vuln 1: SQLi tìm bảng → payload → lấy 3 parts → ghép Flag 1 | Payload list trong ANSWER_KEY |
| 3 | Demo Vuln 2: Business Logic (Credits Hack) → nhập số âm → mua item → Flag 2 | Tài khoản student1 sẵn sàng |
| 4 | Demo Vuln 3: Supply Chain → SQLi đổi URL → Admin click update → Flag 3 | Cần server hacker giả lập |
| 5 | So sánh secure version (before/after) | Mở 2 tabs: vuln vs secure (search, store, admin) |
| 6 | Q&A giảng viên | Thuộc nguyên nhân + cách vá |

---

## ⚠️ Việc Cần Làm Trước Khi Nộp

1. **Điền thông tin thành viên** vào REPORT_DBS401.md (tên, MSSV, giảng viên, ngày nộp).
2. **Test toàn bộ flow** trên máy clean một lần để đảm bảo không có bug.
3. **Xác định log_id thực tế** của TRANSCRIPT_EXPORT_HIDDEN trong ANSWER_KEY (phụ thuộc thứ tự insert).
4. **Chạy init_passwords.php** để đảm bảo bcrypt hash được tạo đúng.
5. **Kiểm tra transcript TXN-099-2024-S1** sau khi seed để xác nhận admin_ref_id hiển thị đúng.
6. **Không commit ANSWER_KEY.md** lên GitHub public nếu có.
7. **Đóng gói ZIP** theo cấu trúc: `DBS401_Group02_[TenDeTai].zip`.

---

*DBS401 – Group 02*
