<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>

    <Head title="Điều Khoản & Chính Sách | Ninh Bình HomeStay" />
    <MainLayout>
        <!-- BANNER -->
        <div class="banner">
            <img src="/anh/banner.png" alt="banner">
            <div class="banner-text">
                <h1>Điều Khoản & Điều Kiện</h1>
                <p><Link href="/">Trang Chủ</Link> / Điều Khoản</p>
            </div>
        </div>
        <div class="baonoidung">
            <aside class="sidebar">
                <div class="toc-card">
                    <p class="toc-title">Mục lục</p>
                    <ul class="toc-list">
                        <li><a href="#s1"><span class="toc-num">01.</span>Giới thiệu chung</a></li>
                        <li><a href="#s2"><span class="toc-num">02.</span>Đăng ký & xác minh</a></li>
                        <li><a href="#s3"><span class="toc-num">03.</span>Đăng ký tạm trú</a></li>
                        <li><a href="#s4"><span class="toc-num">04.</span>Hợp đồng & thanh toán</a></li>
                        <li><a href="#s5"><span class="toc-num">05.</span>Quyền & nghĩa vụ</a></li>
                        <li><a href="#s6"><span class="toc-num">06.</span>Nội quy lưu trú</a></li>
                        <li><a href="#s7"><span class="toc-num">07.</span>Bảo mật thông tin</a></li>
                        <li><a href="#s8"><span class="toc-num">08.</span>Chấm dứt dịch vụ</a></li>
                        <li><a href="#s9"><span class="toc-num">09.</span>Giải quyết tranh chấp</a></li>
                        <li><a href="#s10"><span class="toc-num">10.</span>Điều khoản khác</a></li>
                    </ul>
                </div>
                <div class="contact-box">
                    <p>Cần hỗ trợ?</p>
                    <a href="tel:+84xxx">📞 Hotline: 0xxx xxx xxx</a>
                    <a href="mailto:info@ninhbinhhomestay.vn" style="margin-top:0.3rem;">✉ info@ninhbinhhomestay.vn</a>
                </div>
            </aside>
            <article>
                <!-- 1. Giới thiệu -->
                <div class="section" id="s1">
                    <div class="section-header">
                        <div class="section-num">01</div>
                        <h2>Giới Thiệu Chung</h2>
                    </div>
                    <p>Chào mừng quý khách đến với website và dịch vụ lưu trú của <strong>Ninh Bình Home Stay</strong>.
                        Tài liệu này quy định các điều khoản và điều kiện áp dụng cho toàn bộ giao dịch, đặt phòng và
                        sinh hoạt trong khuôn viên của chúng tôi tại tỉnh Ninh Bình.</p>
                    <p>Khi đăng ký sử dụng dịch vụ thông qua website hoặc trực tiếp tại cơ sở, quý khách đồng nghĩa với
                        việc đã đọc, hiểu và chấp nhận toàn bộ các điều khoản nêu dưới đây. Nếu không đồng ý với bất kỳ
                        điều khoản nào, vui lòng không tiếp tục sử dụng dịch vụ.</p>
                    <div class="highlight-box">
                        <p>Ninh Bình Home Stay hoạt động hợp pháp theo Giấy chứng nhận đăng ký kinh doanh số [XXX] do
                            UBND tỉnh Ninh Bình cấp, tuân thủ Luật Doanh nghiệp 2020, Luật Nhà ở 2023 và các quy định
                            pháp luật hiện hành.</p>
                    </div>
                </div>

                <!-- 2. Đăng ký & xác minh -->
                <div class="section" id="s2">
                    <div class="section-header">
                        <div class="section-num">02</div>
                        <h2>Đăng Ký Thông Tin & Xác Minh Danh Tính</h2>
                    </div>
                    <p>Để đảm bảo an ninh, trật tự và tuân thủ quy định quản lý cư trú theo pháp luật Việt Nam,
                        <strong>tất cả khách lưu trú phải cung cấp đầy đủ và chính xác thông tin cá nhân</strong> trước
                        khi nhận phòng.
                    </p>

                    <p><strong>Thông tin bắt buộc cung cấp:</strong></p>
                    <ul class="item-list">
                        <li>Họ và tên đầy đủ (theo giấy tờ tùy thân)</li>
                        <li>Ngày, tháng, năm sinh</li>
                        <li>Số Căn cước công dân / CCCD / Hộ chiếu còn hiệu lực</li>
                        <li>Địa chỉ thường trú</li>
                        <li>Số điện thoại và email liên hệ</li>
                        <li>Thời hạn dự kiến lưu trú</li>
                        <li>Mục đích lưu trú (công tác, du lịch, học tập, ...)</li>
                    </ul>

                    <div class="info-box">
                        <p>Chúng tôi có quyền từ chối cung cấp dịch vụ nếu khách lưu trú không xuất trình hoặc cung cấp
                            giấy tờ tùy thân hợp lệ theo quy định. Thông tin giả mạo có thể bị xử lý theo quy định pháp
                            luật.</p>
                    </div>
                    <p class="legal-ref">Cơ sở pháp lý: Khoản 3 Điều 5 Nghị định 154/2024/NĐ-CP; Điều 9 Luật Cư trú 2020
                    </p>
                </div>

                <!-- 3. Đăng ký tạm trú -->
                <div class="section" id="s3">
                    <div class="section-header">
                        <div class="section-num">03</div>
                        <h2>Đăng Ký Tạm Trú & Trách Nhiệm Cư Trú</h2>
                    </div>

                    <div class="two-col">
                        <div class="col-card">
                            <div class="col-title">Trách nhiệm của khách</div>
                            <ul class="item-list" style="margin:0;">
                                <li>Tự thực hiện đăng ký tạm trú theo Điều 27 Luật Cư trú 2020</li>
                                <li>Nộp hồ sơ trong vòng 30 ngày kể từ ngày đến ở</li>
                                <li>Gia hạn tạm trú trước 15 ngày khi hết hạn</li>
                                <li>Chịu toàn bộ trách nhiệm nếu không đăng ký</li>
                            </ul>
                        </div>
                        <div class="col-card">
                            <div class="col-title gold">Hỗ trợ từ chúng tôi</div>
                            <ul class="item-list" style="margin:0;">
                                <li>Cung cấp hợp đồng thuê làm căn cứ chứng minh chỗ ở hợp pháp</li>
                                <li>Hướng dẫn thủ tục đăng ký tạm trú tại Công an xã/phường</li>
                                <li>Hỗ trợ lập danh sách người tạm trú theo mẫu CT01</li>
                                <li>Phối hợp với cơ quan chức năng khi được yêu cầu</li>
                            </ul>
                        </div>
                    </div>

                    <p>Theo <strong>Điều 27 Luật Cư trú 2020</strong>, mọi công dân cư trú ngoài phạm vi đơn vị hành
                        chính cấp xã nơi đã đăng ký thường trú từ 30 ngày trở lên phải đăng ký tạm trú. Thời hạn tạm trú
                        tối đa là 2 năm và có thể được gia hạn nhiều lần.</p>

                    <div class="highlight-box">
                        <p>Lưu ý: Theo Điểm a, Khoản 1, Điều 9 Nghị định 144/2021/NĐ-CP, khách lưu trú không đăng ký tạm
                            trú có thể bị phạt tiền từ 500.000đ đến 1.000.000đ. Ninh Bình Home Stay không chịu trách
                            nhiệm về các vi phạm này của khách lưu trú.</p>
                    </div>

                    <p>Đối với <strong>khách nước ngoài</strong>, chúng tôi có trách nhiệm thông báo lưu trú theo quy
                        định của Luật Nhập cảnh, xuất cảnh, quá cảnh, cư trú của người nước ngoài tại Việt Nam. Hộ chiếu
                        và thị thực phải còn hiệu lực trong suốt thời gian lưu trú.</p>
                    <p class="legal-ref">Cơ sở pháp lý: Điều 27 Luật Cư trú 2020; Nghị định 62/2021/NĐ-CP; Nghị định
                        154/2024/NĐ-CP</p>
                </div>

                <!-- 4. Hợp đồng & Thanh toán -->
                <div class="section" id="s4">
                    <div class="section-header">
                        <div class="section-num">04</div>
                        <h2>Hợp Đồng Thuê & Chính Sách Thanh Toán</h2>
                    </div>
                    <p>Mọi giao dịch thuê phòng đều phải được xác lập bằng <strong>hợp đồng thuê nhà bằng văn
                            bản</strong> theo quy định của Bộ luật Dân sự 2015 và Luật Nhà ở 2023. Hợp đồng có giá trị
                        pháp lý và là căn cứ để giải quyết các tranh chấp phát sinh.</p>

                    <p><strong>Đặt cọc:</strong> Theo Điều 328 Bộ luật Dân sự 2015, khách lưu trú có thể được yêu cầu
                        đặt cọc tương đương 1 đến 2 tháng tiền thuê. Tiền cọc sẽ được hoàn trả đầy đủ sau khi kết thúc
                        hợp đồng nếu không có vi phạm.</p>

                    <ul class="item-list">
                        <li>Thanh toán tiền thuê theo chu kỳ hàng tháng, trước ngày 5 của mỗi tháng</li>
                        <li>Chấp nhận thanh toán bằng tiền mặt, chuyển khoản ngân hàng</li>
                        <li>Tiền điện, nước tính theo chỉ số thực tế, thanh toán cùng tiền thuê</li>
                        <li>Phí dịch vụ internet, vệ sinh chung theo thông báo của cơ sở</li>
                        <li>Mức tăng giá (nếu có) phải thông báo trước ít nhất 30 ngày</li>
                    </ul>

                    <p><strong>Chính sách hủy đặt phòng:</strong> Hủy trước 7 ngày – hoàn tiền 100%; hủy trước 3 ngày –
                        hoàn tiền 50%; hủy dưới 3 ngày – không hoàn tiền, trừ trường hợp bất khả kháng có minh chứng.
                    </p>
                </div>

                <!-- 5. Quyền & Nghĩa vụ -->
                <div class="section" id="s5">
                    <div class="section-header">
                        <div class="section-num">05</div>
                        <h2>Quyền & Nghĩa Vụ Các Bên</h2>
                    </div>

                    <p><strong>Quyền và nghĩa vụ của Ninh Bình Home Stay (Bên cho thuê):</strong></p>
                    <ul class="item-list">
                        <li>Bảo đảm cơ sở vật chất, điều kiện sinh hoạt an toàn, sạch sẽ cho khách lưu trú</li>
                        <li>Bảo trì, sửa chữa các hư hỏng thuộc trách nhiệm của bên cho thuê kịp thời</li>
                        <li>Thu tiền thuê và các chi phí dịch vụ đúng theo thỏa thuận trong hợp đồng</li>
                        <li>Yêu cầu khách cung cấp thông tin và giấy tờ hợp lệ theo quy định pháp luật</li>
                        <li>Phối hợp với cơ quan chức năng trong công tác quản lý an ninh, trật tự</li>
                        <li>Thông báo trước ít nhất 24 giờ khi cần kiểm tra, bảo trì phòng trọ</li>
                    </ul>

                    <p><strong>Quyền và nghĩa vụ của khách lưu trú (Bên thuê):</strong></p>
                    <ul class="item-list">
                        <li>Được sử dụng phòng trọ và các tiện ích chung theo đúng điều khoản hợp đồng</li>
                        <li>Được bảo đảm quyền riêng tư and an toàn trong không gian thuê</li>
                        <li>Thanh toán đầy đủ, đúng hạn các khoản chi phí theo quy định</li>
                        <li>Bảo quản tài sản, thiết bị trong phòng; bồi thường thiệt hại nếu gây hư hỏng</li>
                        <li>Tuân thủ nội quy lưu trú và pháp luật Việt Nam trong suốt thời gian ở trọ</li>
                        <li>Thông báo cho ban quản lý khi có khách đến thăm ở lại qua đêm</li>
                    </ul>
                    <p class="legal-ref">Cơ sở pháp lý: Điều 481, 482 Bộ luật Dân sự 2015; Luật Nhà ở 2023</p>
                </div>

                <!-- 6. Nội quy -->
                <div class="section" id="s6">
                    <div class="section-header">
                        <div class="section-num">06</div>
                        <h2>Nội Quy Lưu Trú</h2>
                    </div>
                    <p>Để duy trì môi trường sống văn minh, an toàn và thoải mái cho tất cả khách lưu trú, quý khách vui
                        lòng tuân thủ các nội quy sau:</p>

                    <ul class="item-list">
                        <li>Giờ giới nghiêm khu vực chung: 23:00 – 06:00 (giữ yên tĩnh, không gây ồn ào)</li>
                        <li>Không hút thuốc trong phòng và khu vực chung trong nhà</li>
                        <li>Không nuôi động vật trong khuôn viên lưu trú nếu không có sự đồng ý bằng văn bản</li>
                        <li>Không tổ chức tụ tập, tiệc tùng gây ảnh hưởng đến khách lưu trú khác</li>
                        <li>Không tàng trữ, sử dụng các chất cấm hoặc vũ khí bất hợp pháp</li>
                        <li>Giữ vệ sinh chung, phân loại rác theo hướng dẫn của cơ sở</li>
                        <li>Thông báo ngay khi phát hiện sự cố an toàn, cháy nổ, rò rỉ điện nước</li>
                        <li>Tuân thủ quy định phòng cháy chữa cháy theo Nghị định 104/2024/NĐ-CP</li>
                    </ul>

                    <div class="info-box">
                        <p>Vi phạm nội quy tùy mức độ có thể dẫn đến nhắc nhở, phạt tiền bồi thường thiệt hại, hoặc chấm
                            dứt hợp đồng trước thời hạn mà không hoàn trả tiền cọc.</p>
                    </div>
                </div>

                <!-- 7. Bảo mật -->
                <div class="section" id="s7">
                    <div class="section-header">
                        <div class="section-num">07</div>
                        <h2>Bảo Mật Thông Tin Cá Nhân</h2>
                    </div>
                    <p>Ninh Bình Home Stay cam kết bảo vệ thông tin cá nhân của khách lưu trú theo quy định của
                        <strong>Nghị định 13/2023/NĐ-CP về bảo vệ dữ liệu cá nhân</strong>.
                    </p>

                    <ul class="item-list">
                        <li>Thông tin cá nhân chỉ được thu thập cho mục đích quản lý lưu trú và tuân thủ pháp luật</li>
                        <li>Không chia sẻ, bán hoặc tiết lộ thông tin cho bên thứ ba vì mục đích thương mại</li>
                        <li>Cung cấp thông tin cho cơ quan nhà nước có thẩm quyền khi được yêu cầu theo đúng thủ tục
                            pháp luật</li>
                        <li>Dữ liệu được lưu trữ an toàn và chỉ được giữ trong thời gian cần thiết</li>
                        <li>Khách lưu trú có quyền yêu cầu xem, chỉnh sửa hoặc xóa thông tin cá nhân của mình</li>
                    </ul>
                    <p class="legal-ref">Cơ sở pháp lý: Nghị định 13/2023/NĐ-CP về bảo vệ dữ liệu cá nhân</p>
                </div>

                <!-- 8. Chấm dứt -->
                <div class="section" id="s8">
                    <div class="section-header">
                        <div class="section-num">08</div>
                        <h2>Chấm Dứt Dịch Vụ & Hợp Đồng</h2>
                    </div>
                    <p>Hợp đồng thuê có thể được chấm dứt trong các trường hợp sau:</p>
                    <ul class="item-list">
                        <li>Hết thời hạn thuê theo thỏa thuận trong hợp đồng</li>
                        <li>Hai bên cùng thỏa thuận chấm dứt hợp đồng trước thời hạn</li>
                        <li>Khách lưu trú vi phạm nghiêm trọng nội quy hoặc điều khoản hợp đồng</li>
                        <li>Khách lưu trú không thanh toán tiền thuê trong vòng 15 ngày sau hạn chót</li>
                        <li>Bất khả kháng (thiên tai, dịch bệnh, quyết định của cơ quan nhà nước, ...)</li>
                    </ul>
                    <p>Khi chấm dứt hợp đồng, khách lưu trú phải thực hiện thủ tục trả phòng, bàn giao nguyên vẹn tài
                        sản và hoàn tất các nghĩa vụ tài chính còn lại. Ninh Bình Home Stay sẽ hoàn trả tiền đặt cọc
                        trong vòng <strong>7 ngày làm việc</strong> sau khi xác nhận không có vi phạm.</p>
                </div>

                <!-- 9. Tranh chấp -->
                <div class="section" id="s9">
                    <div class="section-header">
                        <div class="section-num">09</div>
                        <h2>Giải Quyết Tranh Chấp</h2>
                    </div>
                    <p>Mọi tranh chấp phát sinh từ hoặc liên quan đến điều khoản này ưu tiên được giải quyết theo trình
                        tự:</p>
                    <ul class="item-list">
                        <li><strong>Thương lượng:</strong> Các bên chủ động trao đổi, tìm kiếm giải pháp phù hợp trong
                            vòng 15 ngày</li>
                        <li><strong>Hòa giải:</strong> Thông qua trung gian hòa giải hoặc tổ hòa giải tại địa phương nếu
                            thương lượng thất bại</li>
                        <li><strong>Khiếu nại:</strong> Gửi đơn khiếu nại đến cơ quan quản lý nhà nước có thẩm quyền tại
                            tỉnh Ninh Bình</li>
                        <li><strong>Tòa án:</strong> Tranh chấp được giải quyết tại Tòa án nhân dân có thẩm quyền tại
                            tỉnh Ninh Bình theo quy định pháp luật Việt Nam</li>
                    </ul>
                </div>

                <!-- 10. Điều khoản khác -->
                <div class="section" id="s10">
                    <div class="section-header">
                        <div class="section-num">10</div>
                        <h2>Điều Khoản Khác</h2>
                    </div>
                    <p><strong>Sửa đổi điều khoản:</strong> Ninh Bình Home Stay có quyền sửa đổi, bổ sung điều khoản này
                        bất kỳ lúc nào. Phiên bản cập nhật sẽ được đăng tải trên website và thông báo đến khách lưu trú
                        ít nhất 15 ngày trước khi có hiệu lực.</p>
                    <p><strong>Bất khả kháng:</strong> Các bên không chịu trách nhiệm về những tổn thất gây ra bởi các
                        sự kiện ngoài tầm kiểm soát hợp lý như thiên tai, dịch bệnh, chiến tranh, quyết định của cơ quan
                        nhà nước.</p>
                    <p><strong>Hiệu lực từng phần:</strong> Nếu bất kỳ điều khoản nào bị cơ quan có thẩm quyền tuyên bố
                        vô hiệu, các điều khoản còn lại vẫn có đầy đủ hiệu lực pháp lý.</p>
                    <p><strong>Ngôn ngữ áp dụng:</strong> Điều khoản này được lập bằng tiếng Việt. Trong trường hợp có
                        bản dịch, bản tiếng Việt được ưu tiên áp dụng.</p>

                    <div class="highlight-box">
                        <p>Mọi thắc mắc về điều khoản và điều kiện, vui lòng liên hệ: <strong>Ninh Bình Home
                                Stay</strong> – Địa chỉ: [Địa chỉ cụ thể], Ninh Bình – Điện thoại: 0xxx xxx xxx – Email:
                            info@ninhbinhhomestay.vn</p>
                    </div>
                </div>

            </article>
        </div>
    </MainLayout>
</template>

<style>
@import "../../css/dieukhoan.css";
@import '../../css/responsive/responsivegioithieu.css';
@import '../../css/responsive/responsive.css';
</style>