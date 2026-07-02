<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = \App\Models\User::where('role', 'admin')->first();
        $adminId = $admin ? $admin->id : 1;

        $posts = [
            [
                'title' => 'Top 5 Homestay view đẹp nhất tại Tràng An, Ninh Bình',
                'slug' => 'top-5-homestay-view-dep-nhat-tai-trang-an-ninh-binh',
                'image' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Khám phá ngay danh sách 5 homestay có tầm nhìn tuyệt đẹp hướng ra danh thắng Tràng An, giúp bạn có kỳ nghỉ dưỡng trọn vẹn và nhiều ảnh check-in cực chất.',
                'content' => '<p>Ninh Bình luôn là điểm đến hấp dẫn du khách trong và ngoài nước bởi vẻ đẹp kỳ vĩ của sông núi, hang động. Để hành trình khám phá vùng đất cố đô thêm phần trọn vẹn, việc lựa chọn một nơi lưu trú có không gian đẹp, gần gũi thiên nhiên là vô cùng quan trọng. Dưới đây là danh sách 5 homestay view đẹp nhất tại khu vực Tràng An mà bạn không nên bỏ lỡ.</p>
                <h4>1. Tràng An River View Homestay</h4>
                <p>Nằm ngay bên dòng sông sào khê thơ mộng, Tràng An River View mang lại cảm giác bình yên đến lạ kỳ. Buổi sáng thức dậy, bạn có thể phóng tầm mắt ngắm nhìn sương mờ bảng lảng trên các dãy núi đá vôi dựng đứng.</p>
                <h4>2. Ninh Bình Valley Homestay</h4>
                <p>Nổi tiếng với thiết kế hòa quyện vào thung lũng, đây là địa điểm lý tưởng cho những ai muốn lánh xa khói bụi thành thị. Các phòng nghỉ dạng bungalow tre nứa mộc mạc nhưng đầy đủ tiện nghi.</p>
                <h4>3. Hoa Lu Eco Homestay</h4>
                <p>Tọa lạc gần cố đô Hoa Lư cổ kính, homestay này sở hữu không gian sân vườn rộng rãi và hướng núi tuyệt đẹp. Đội ngũ nhân viên thân thiện và các món ăn đặc sản Ninh Bình tại đây luôn được đánh giá cao.</p>',
                'category' => 'Tin Tức',
                'tags' => 'Ninh Binh, Homestay, Du Lich, Trang An',
                'author_id' => $adminId,
                'views' => 125,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'title' => 'Kinh nghiệm thuê phòng trọ giá rẻ cho sinh viên tại Ninh Bình',
                'slug' => 'kinh-nghiem-thue-phong-tro-gia-re-cho-sinh-vien-tai-ninh-binh',
                'image' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Hướng dẫn chi tiết các bước tìm phòng trọ, kinh nghiệm thương lượng giá cả, đọc hợp đồng thuê nhà để tránh những rủi ro không đáng có cho tân sinh viên.',
                'content' => '<p>Tìm kiếm một căn phòng trọ ưng ý, giá cả phải chăng luôn là thử thách lớn đối với các bạn sinh viên, đặc biệt là những bạn tân sinh viên mới nhập học tại các trường Cao đẳng, Đại học ở Ninh Bình. Bài viết dưới đây sẽ chia sẻ một số kinh nghiệm quý báu giúp các bạn tìm được phòng trọ như ý.</p>
                <h4>1. Xác định khu vực tìm kiếm phòng trọ</h4>
                <p>Nên ưu tiên các phòng trọ nằm gần trường học hoặc các tuyến xe bus thuận tiện. Khu vực gần trường Đại học Hoa Lư thường có mức giá phòng đa dạng phù hợp túi tiền sinh viên.</p>
                <h4>2. Kiểm tra kỹ cơ sở vật chất trước khi đặt cọc</h4>
                <p>Hãy đảm bảo hệ thống điện, nước, nhà vệ sinh hoạt động bình thường. Đọc kỹ đồng hồ điện nước và chụp ảnh lại làm bằng chứng để tránh tranh chấp chỉ số sau này.</p>
                <h4>3. Đọc kỹ hợp đồng thuê nhà</h4>
                <p>Hợp đồng cần thể hiện rõ thông tin tiền đặt cọc, tiền thuê hàng tháng, chi phí dịch vụ (WiFi, rác, điện, nước) và thời hạn thông báo trước khi chuyển đi (thường là 30 ngày).</p>',
                'category' => 'Tin Tức',
                'tags' => 'Sinh Vien, Phong Tro, Gia Re, Kinh Nghiem',
                'author_id' => $adminId,
                'views' => 340,
                'created_at' => now()->subDays(9),
                'updated_at' => now()->subDays(9),
            ],
            [
                'title' => 'Tuyển dụng nhân viên lễ tân Homestay tại Tam Cốc - Bích Động',
                'slug' => 'tuyen-dung-nhan-vien-le-tan-homestay-tai-tam-coc-bich-dong',
                'image' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Cơ hội việc làm hấp dẫn cho các bạn trẻ năng động, yêu thích ngành du lịch - khách sạn tại Ninh Bình. Yêu cầu giao tiếp tiếng Anh cơ bản.',
                'content' => '<p>Homestay Tam Cốc Green View đang cần tuyển gấp 02 nhân viên lễ tân ca ngày và ca đêm làm việc tại khu vực Tam Cốc, Hoa Lư, Ninh Bình.</p>
                <h4>Mô tả công việc:</h4>
                <ul>
                    <li>Thực hiện thủ tục check-in, check-out cho khách lưu trú (chủ yếu là khách nước ngoài).</li>
                    <li>Tư vấn lịch trình tham quan, đặt vé xe, dịch vụ thuê xe cho khách hàng.</li>
                    <li>Giải đáp các thắc mắc và hỗ trợ khách hàng trong thời gian lưu trú.</li>
                </ul>
                <h4>Yêu cầu công việc:</h4>
                <ul>
                    <li>Nhanh nhẹn, trung thực, có thái độ làm việc tốt.</li>
                    <li>Giao tiếp tiếng Anh cơ bản (sẽ được đào tạo thêm).</li>
                    <li>Ưu tiên ứng viên có kinh nghiệm làm lễ tân hoặc có hộ khẩu tại Ninh Bình.</li>
                </ul>
                <h4>Quyền lợi được hưởng:</h4>
                <ul>
                    <li>Mức lương hấp dẫn: 5.000.000đ - 7.000.000đ + thưởng doanh thu dịch vụ.</li>
                    <li>Môi trường làm việc năng động, nâng cao khả năng giao tiếp ngoại ngữ.</li>
                    <li>Hỗ trợ ăn ca tại Homestay.</li>
                </ul>',
                'category' => 'Việc Làm',
                'tags' => 'Viec Lam, Le Tan, Tam Coc, Tuyen Dung',
                'author_id' => $adminId,
                'views' => 89,
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(8),
            ],
            [
                'title' => 'Những lưu ý quan trọng khi ký hợp đồng thuê nhà mà bạn cần biết',
                'slug' => 'nhung-luu-y-quan-trong-khi-ky-hop-dong-thue-nha-ma-ban-can-biet',
                'image' => 'https://images.unsplash.com/photo-1450133064473-71024230f91b?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Các điều khoản pháp lý quan trọng, tiền đặt cọc, quyền hạn của người thuê và chủ nhà mà bạn bắt buộc phải kiểm tra kỹ trước khi ký tên.',
                'content' => '<p>Ký hợp đồng thuê nhà là một bước pháp lý cực kỳ quan trọng để bảo vệ quyền lợi của cả chủ nhà và người đi thuê. Tuy nhiên, rất nhiều bạn thường bỏ qua các chi tiết nhỏ dẫn đến những tranh chấp không đáng có.</p>
                <h4>1. Xác định rõ tiền cọc và cách thức hoàn trả</h4>
                <p>Số tiền cọc là bao nhiêu tháng thuê? Khi kết thúc hợp đồng hoặc chuyển đi trước hạn thì điều kiện hoàn trả cọc như thế nào? Tất cả phải ghi rõ bằng văn bản.</p>
                <h4>2. Thời gian và phương thức thanh toán tiền nhà</h4>
                <p>Cần làm rõ đóng tiền nhà vào ngày nào trong tháng, hình thức chuyển khoản hay tiền mặt, và có phát sinh phạt nếu trả chậm hay không.</p>
                <h4>3. Trách nhiệm bảo trì và sửa chữa thiết bị</h4>
                <p>Thông thường, các hư hỏng tự nhiên lớn (mái dột, tường thấm, hỏng đường ống nước âm tường) chủ nhà phải sửa. Còn các hư hỏng nhỏ do hao mòn sử dụng (hỏng bóng đèn, vỡ kính) người thuê tự khắc phục.</p>',
                'category' => 'Tin Tức',
                'tags' => 'Hop Dong, Phap Ly, Thue Nha, Kinh Nghiem',
                'author_id' => $adminId,
                'views' => 210,
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(7),
            ],
            [
                'title' => 'Cẩm nang du lịch Ninh Bình tự túc 2 ngày 1 đêm tiết kiệm nhất',
                'slug' => 'cam-nang-du-lich-ninh-binh-tu-tuc-2-ngay-1-dem-tiet-kiem-nhat',
                'image' => 'https://images.unsplash.com/photo-1528127269322-539801943592?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Chia sẻ lịch trình chi tiết, gợi ý phương tiện di chuyển, địa điểm ăn uống, nghỉ dưỡng lý tưởng cho chuyến du lịch Ninh Bình trọn vẹn.',
                'content' => '<p>Ninh Bình chỉ cách Hà Nội khoảng 100km, rất thích hợp cho những chuyến phượt ngắn ngày vào cuối tuần. Hãy bỏ túi ngay cẩm nang du lịch tự túc cực kỳ tiết kiệm sau đây.</p>
                <h4>Lịch trình Ngày 1: Tràng An - Hang Múa</h4>
                <p>Sáng: Xuất phát từ Hà Nội bằng xe máy hoặc xe limousine. Đến Ninh Bình check-in phòng nghỉ, thuê xe máy đi Tràng An trải nghiệm chèo đò ngắm hang động hùng vĩ. Chiều: Leo đỉnh Hang Múa ngắm toàn cảnh thung lũng lúa chín từ trên cao.</p>
                <h4>Lịch trình Ngày 2: Chùa Bái Đính - Đầm Vân Long</h4>
                <p>Sáng: Viếng chùa Bái Đính - ngôi chùa sở hữu nhiều kỷ lục châu Á. Chiều: Khám phá khu bảo tồn thiên nhiên Đầm Vân Long thanh bình trước khi lên xe quay trở về.</p>',
                'category' => 'Tin Tức',
                'tags' => 'Du Lich, Phuot, Ninh Binh, Tiet Kiem',
                'author_id' => $adminId,
                'views' => 450,
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(6),
            ],
            [
                'title' => 'Tuyển dụng nhân viên buồng phòng Homestay tại Hoa Lư',
                'slug' => 'tuyen-dung-nhan-vien-buong-phong-homestay-tai-hoa-lu',
                'image' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Tuyển gấp nhân viên dọn dẹp buồng phòng homestay tại Hoa Lư, làm việc xoay ca linh hoạt, có hỗ trợ nhà ở cho nhân viên ở xa.',
                'content' => '<p>Do nhu cầu mở rộng dịch vụ đón khách mùa hè, Homestay Mountain View Ninh Bình cần tuyển dụng 03 nhân viên buồng phòng.</p>
                <h4>Công việc chính:</h4>
                <ul>
                    <li>Dọn dẹp, chuẩn bị phòng nghỉ sạch sẽ trước khi khách check-in và sau khi check-out.</li>
                    <li>Thay ga trải giường, vỏ gối, bổ sung khăn tắm và các vật phẩm cá nhân vào phòng nghỉ.</li>
                    <li>Báo cáo kịp thời các thiết bị hư hỏng trong phòng nghỉ cho quản lý.</li>
                </ul>
                <h4>Yêu cầu:</h4>
                <ul>
                    <li>Sức khỏe tốt, chăm chỉ, tỉ mỉ, có trách nhiệm trong công việc.</li>
                    <li>Không yêu cầu bằng cấp hay ngoại ngữ, sẽ được hướng dẫn công việc bài bản.</li>
                </ul>',
                'category' => 'Việc Làm',
                'tags' => 'Buong Phong, Viec Lam, Hoa Lu, Tuyen Dung',
                'author_id' => $adminId,
                'views' => 75,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'title' => 'Các địa điểm ăn vặt ngon rẻ không thể bỏ qua tại thành phố Ninh Bình',
                'slug' => 'cac-dia-diem-an-vat-ngon-re-khong-the-bo-qua-tai-thanh-pho-ninh-binh',
                'image' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Điểm danh những món ăn đường phố nổi tiếng và địa chỉ các quán ăn vặt vừa ngon vừa túi tiền cho học sinh, sinh viên tại Ninh Bình.',
                'content' => '<p>Bên cạnh các món dê núi cơm cháy nổi tiếng, ẩm thực đường phố tại Ninh Bình cũng vô cùng phong phú và có mức giá cực kỳ học sinh sinh viên. Cùng điểm qua một số địa chỉ ăn vặt nổi bật.</p>
                <h4>1. Phố ăn vặt cổng trường Đại học Hoa Lư</h4>
                <p>Nơi đây tập trung vô vàn các xe đồ ăn nhanh như bánh tráng trộn, trà sữa, xiên nướng, bánh mì muối ớt... với mức giá chỉ từ 10.000đ.</p>
                <h4>2. Bánh tráng nướng cổng Chùa Vàng</h4>
                <p>Không gian rộng rãi, thoáng mát, thích hợp tụ tập bạn bè vào buổi tối. Bánh tráng nướng nóng hổi giòn rụm kết hợp với sữa đậu nành mát lạnh là combo tuyệt vời.</p>',
                'category' => 'Tin Tức',
                'tags' => 'An Vat, Sinh Vien, Ngon Re, Am Thuc',
                'author_id' => $adminId,
                'views' => 520,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],
            [
                'title' => 'Tuyển dụng hướng dẫn viên du lịch nội địa tại Tràng An',
                'slug' => 'tuyen-dung-huong-dan-vien-du-lich-noi-dia-tai-trang-an',
                'image' => 'https://images.unsplash.com/photo-1527631746610-bca00a040d60?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Công ty du lịch nội địa tuyển hướng dẫn viên dẫn các tour du lịch Ninh Bình - Tràng An - Bái Đính, yêu cầu am hiểu văn hóa địa phương.',
                'content' => '<p>Công ty TNHH Lữ hành Tràng An Tour tuyển dụng nhân sự Hướng dẫn viên du lịch nội địa làm việc tại Ninh Bình.</p>
                <h4>Nhiệm vụ:</h4>
                <ul>
                    <li>Dẫn đoàn khách tham quan các danh thắng nổi tiếng tại Ninh Bình theo lịch trình có sẵn.</li>
                    <li>Thuyết minh, giới thiệu các giá trị văn hóa, lịch sử đặc sắc của địa phương cho khách du lịch.</li>
                    <li>Chăm sóc, đảm bảo an toàn và hỗ trợ nhu cầu của khách trong suốt hành trình.</li>
                </ul>
                <h4>Yêu cầu:</h4>
                <ul>
                    <li>Có thẻ hướng dẫn viên du lịch nội địa hoặc quốc tế còn hạn.</li>
                    <li>Giao tiếp tự tin, giọng nói truyền cảm, không ngọng, nói lắp.</li>
                </ul>',
                'category' => 'Việc Làm',
                'tags' => 'Huong Dan Vien, Du Lich, Viec Lam, Tuyen Dung',
                'author_id' => $adminId,
                'views' => 140,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'title' => 'Tải mẫu biên bản bàn giao thiết bị phòng trọ tiêu chuẩn mới nhất',
                'slug' => 'tai-mau-bien-ban-ban-giao-thiet-bi-phong-tro-tieu-chuan-moi-nhat',
                'image' => 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Tải ngay mẫu biên bản bàn giao tài sản, đồ đạc trong phòng trọ khi bắt đầu thuê để bảo vệ quyền lợi của cả chủ nhà và người thuê.',
                'content' => '<p>Khi nhận phòng trọ mới, việc lập biên bản kiểm tra và bàn giao thiết bị hiện có là bước đệm pháp lý thiết yếu. Điều này giúp hai bên xác định rõ hiện trạng vật chất tại thời điểm bắt đầu thuê, tránh việc đền bù oan khi chuyển đi.</p>
                <h4>Biên bản cần bao gồm:</h4>
                <ul>
                    <li>Thông tin người bàn giao (chủ nhà) và người nhận bàn giao (người thuê).</li>
                    <li>Danh sách chi tiết thiết bị kèm hiện trạng (ví dụ: máy lạnh hiệu Daikin hoạt động tốt, tủ lạnh Hitachi bị xước nhẹ ở cửa, tường phòng ngủ có 1 vết ố...).</li>
                    <li>Chữ ký xác nhận của hai bên.</li>
                </ul>',
                'category' => 'Tin Tức',
                'tags' => 'Tai Lieu, Mau Don, Bien Ban, Phong Tro',
                'author_id' => $adminId,
                'views' => 95,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'title' => 'Tuyển dụng nhân viên chạy bàn cafe sân vườn tại TP. Ninh Bình',
                'slug' => 'tuyen-dung-nhan-vien-chay-ban-cafe-san-vuon-tai-tp-ninh-binh',
                'image' => 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Tuyển nhân viên phục vụ, chạy bàn bán thời gian hoặc toàn thời gian cho quán cafe phong cách sân vườn tại trung tâm TP. Ninh Bình.',
                'content' => '<p>Quán Cố Đô Cafe cần tuyển dụng 04 bạn nhân viên phục vụ chạy bàn ca sáng và ca tối.</p>
                <h4>Thời gian làm việc:</h4>
                <ul>
                    <li>Ca sáng: 07h00 - 12h00</li>
                    <li>Ca tối: 17h30 - 22h30</li>
                </ul>
                <h4>Mức lương:</h4>
                <ul>
                    <li>Lương thỏa thuận: từ 20.000đ - 25.000đ/giờ + thưởng doanh số cuối tháng.</li>
                </ul>',
                'category' => 'Việc Làm',
                'tags' => 'Cafe, Phuc Vu, Viec Lam, Ban Thoi Gian',
                'author_id' => $adminId,
                'views' => 112,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'title' => 'Tuyển dụng nhân viên kế toán Homestay tại Hoa Lư, Ninh Bình',
                'slug' => 'tuyen-dung-nhan-vien-ke-toan-homestay-tai-hoa-lu-ninh-binh',
                'image' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Tuyển nhân viên kế toán nội bộ làm việc tại văn phòng homestay ở Tràng An, yêu cầu biết sử dụng phần mềm Misa và Excel cơ bản.',
                'content' => '<p>Homestay Tràng An Retreat cần tuyển gấp 01 nhân viên kế toán nội bộ.</p>
                <h4>Yêu cầu:</h4>
                <ul>
                    <li>Tốt nghiệp Cao đẳng/Đại học chuyên ngành kế toán, tài chính.</li>
                    <li>Sử dụng thành thạo Word, Excel. Biết sử dụng phần mềm Misa là một lợi thế.</li>
                    <li>Cẩn thận, trung thực, có tinh thần trách nhiệm.</li>
                </ul>
                <h4>Quyền lợi:</h4>
                <ul>
                    <li>Mức lương: 6.500.000đ - 8.000.000đ tùy năng lực.</li>
                    <li>Đóng BHXH đầy đủ theo luật lao động.</li>
                </ul>',
                'category' => 'Việc Làm',
                'tags' => 'Ke Toan, Viec Lam, Hoa Lu, Tuyen Dung',
                'author_id' => $adminId,
                'views' => 64,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'title' => 'Chia sẻ 5 quán cơm cháy ngon nhất định phải thử ở Ninh Bình',
                'slug' => 'chia-se-5-quan-com-chay-ngon-nhat-dinh-phai-thu-o-ninh-binh',
                'image' => 'https://images.unsplash.com/photo-1611143669185-af224c5e3252?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Khám phá bản đồ ẩm thực cố đô với 5 địa chỉ quán cơm cháy chà bông thơm ngon giòn rụm nổi tiếng nhất được người bản địa khuyên dùng.',
                'content' => '<p>Cơm cháy là món đặc sản Ninh Bình nổi tiếng nhất. Hãy cùng khám phá 5 địa chỉ mua cơm cháy giòn rụm, chà bông ngập tràn được người dân địa phương vô cùng yêu thích.</p>
                <h4>1. Cơm cháy Cổ Hoàng</h4>
                <p>Nổi tiếng với nước sốt dê gia truyền đi kèm cơm cháy giòn tan.</p>
                <h4>2. Cơm cháy Đại Long</h4>
                <p>Thương hiệu đóng gói sẵn nổi tiếng, phù hợp mua làm quà tặng du lịch.</p>',
                'category' => 'Tin Tức',
                'tags' => 'Com Chay, Dac San, Ninh Binh, Am Thuc',
                'author_id' => $adminId,
                'views' => 198,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'title' => 'Kế hoạch tổ chức tuần lễ du lịch Sắc Vàng Tam Cốc năm nay',
                'slug' => 'ke-hoach-to-chức-tuan-le-du-lich-sac-vang-tam-coc-nam-nay',
                'image' => 'https://images.unsplash.com/photo-1508739773434-c26b3d09e071?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Cập nhật thời gian khai mạc, lịch trình chi tiết và các hoạt động văn hóa nghệ thuật đặc sắc diễn ra tại Tam Cốc Ninh Bình trong tuần lễ vàng.',
                'content' => '<p>Tuần lễ du lịch Ninh Bình với chủ đề "Sắc vàng Tam Cốc - Tràng An" là sự kiện quảng bá hình ảnh thiên nhiên, văn hóa đặc sắc của tỉnh đến bạn bè bốn phương.</p>
                <p>Sự kiện sẽ khai mạc vào cuối tháng 5 khi các cánh đồng lúa hai bên dòng sông Ngô Đồng chín vàng ruộm. Du khách sẽ được thưởng thức trình diễn nghệ thuật múa rối nước, biểu diễn hát xẩm và chèo thuyền ngắm lúa.</p>',
                'category' => 'Tin Tức',
                'tags' => 'Tam Coc, Du Lich, Sac Vang, Su Kien',
                'author_id' => $adminId,
                'views' => 310,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'title' => 'Tuyển dụng nhân viên tư vấn tour du lịch online làm việc tại nhà',
                'slug' => 'tuyen-dung-nhan-vien-tu-van-tour-du-lich-online-lam-viec-tai-nha',
                'image' => 'https://images.unsplash.com/photo-1521791136368-1a883a75a31f?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Tuyển cộng tác viên tư vấn tour du lịch trực tuyến, hỗ trợ khách hàng đặt phòng homestay, làm việc online linh hoạt phù hợp với sinh viên kiếm thêm thu nhập.',
                'content' => '<p>Công ty Du lịch Ninh Bình Star cần tuyển 05 cộng tác viên tư vấn và chốt sales tour online.</p>
                <h4>Mô tả công việc:</h4>
                <p>Tư vấn khách hàng qua Fanpage/Zalo về các gói tour du lịch Ninh Bình, hỗ trợ book phòng homestay, khách sạn. Công việc hoàn toàn làm online tại nhà.</p>
                <h4>Quyền lợi:</h4>
                <p>Thu nhập theo hoa hồng hấp dẫn từ 10% - 15% trên mỗi dịch vụ chốt thành công.</p>',
                'category' => 'Việc Làm',
                'tags' => 'Viec Lam, Online, Tu Van, Sales Tour',
                'author_id' => $adminId,
                'views' => 155,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'title' => 'Cách tính toán chi phí điện nước phòng trọ để không bị hớ',
                'slug' => 'cach-tinh-toan-chi-phi-dien-nuoc-phong-tro-de-khong-bi-ho',
                'image' => 'https://images.unsplash.com/photo-1473341304170-971dccb5ac1e?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Bí quyết giúp người đi thuê trọ tự tính toán chỉ số điện nước, giá bậc thang nhà nước và nhận biết các dấu hiệu công tơ điện bị rò rỉ hoặc chạy sai.',
                'content' => '<p>Chi phí điện nước luôn chiếm một phần không nhỏ trong sinh hoạt phí hàng tháng của các bạn thuê phòng trọ. Việc tự kiểm tra chỉ số và hiểu cách tính tiền sẽ giúp bạn tự chủ tài chính tốt hơn.</p>
                <h4>1. Tìm hiểu đơn giá quy định</h4>
                <p>Chủ nhà trọ thu tiền điện theo giá kinh doanh cố định hay theo lũy tiến của nhà nước? Hãy thỏa thuận chi tiết trong hợp đồng.</p>
                <h4>2. Theo dõi chỉ số công tơ định kỳ</h4>
                <p>Ghi lại số điện cũ và mới vào ngày chốt chỉ số hàng tháng, chụp ảnh lại mặt công tơ để đối chiếu.</p>',
                'category' => 'Tin Tức',
                'tags' => 'Dien Nuoc, Phong Tro, Chi Phi, Meo Vat',
                'author_id' => $adminId,
                'views' => 148,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'title' => 'Tuyển nhân viên pha chế (Bartender) tại Cafe Homestay Tam Cốc',
                'slug' => 'tuyen-nhan-vien-pha-che-bartender-tai-cafe-homestay-tam-coc',
                'image' => 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Quán bar sân vườn thuộc Homestay cần tuyển gấp nhân viên pha chế đồ uống sinh tố, cocktail, lương thưởng theo ca cực hấp dẫn.',
                'content' => '<p>Quán cafe sân vườn thuộc Eco Homestay Tam Cốc tuyển 02 Bartender pha chế nước ép, sinh tố và cocktail.</p>
                <h4>Yêu cầu:</h4>
                <p>Nhanh nhẹn, sạch sẽ, có kiến thức cơ bản về pha chế sinh tố, cà phê máy và nước ép trái cây. Có tiếng Anh giao tiếp là lợi thế lớn.</p>
                <h4>Mức lương:</h4>
                <p>25.000đ - 30.000đ/giờ + thưởng doanh số thức uống.</p>',
                'category' => 'Việc Làm',
                'tags' => 'Pha Che, Bartender, Cafe, Tuyen Dung',
                'author_id' => $adminId,
                'views' => 99,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'title' => 'Giải mã sức hút của khu du lịch sinh thái Đầm Vân Long',
                'slug' => 'giai-ma-suc-hut-cua-khu-du-lich-sinh-thai-dam-van-long',
                'image' => 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Nơi quay bộ phim bom tấn Kong: Skull Island sở hữu khung cảnh hoang sơ trữ tình, đàn cò trắng bay lượn ngập trời thu hút hàng ngàn du khách.',
                'content' => '<p>Đầm Vân Long là khu bảo tồn thiên nhiên ngập nước lớn nhất vùng đồng bằng Bắc Bộ. Đây là bức tranh sơn thủy hữu tình làm đắm say bao tâm hồn xê dịch.</p>
                <p>Khi chèo thuyền xuyên qua đầm nước trong vắt đến mức nhìn rõ rong rêu phía dưới, bạn sẽ ngỡ như đang đi lạc vào một cõi tiên cảnh thanh bình, không tiếng còi xe, chỉ có tiếng mái chèo nhẹ khua và tiếng chim kêu vang.</p>',
                'category' => 'Tin Tức',
                'tags' => 'Van Long, Du Lich, Sinh Thai, Khám Pha',
                'author_id' => $adminId,
                'views' => 280,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'title' => 'Tìm bạn ở ghép căn hộ 2 phòng ngủ gần khu công nghiệp Gián Khẩu',
                'slug' => 'tim-ban-o-ghep-can-ho-2-phong-ngu-gan-khu-cong-nghiep-gian-khau',
                'image' => 'https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?auto=format&fit=crop&w=800&q=80',
                'summary' => 'Cần tìm 01 bạn nam hoặc nữ ở ghép căn hộ chung cư mini sạch sẽ, tiện nghi đầy đủ máy lạnh tủ lạnh máy giặt, chi phí chia đôi tiết kiệm.',
                'content' => '<p>Mình cần tìm 01 bạn ở ghép share phòng chung cư mini 45m2 mới coong gần khu công nghiệp Gián Khẩu.</p>
                <p>Phòng đã trang bị sẵn máy lạnh, tủ lạnh, máy giặt, tủ bếp nấu ăn. Chi phí thuê phòng là 3.000.000đ/tháng chia đôi mỗi người 1.500.000đ + điện nước chia theo hóa đơn.</p>',
                'category' => 'Tin Tức',
                'tags' => 'O Ghep, Sinh Vien, Chung Cu, Phong Tro',
                'author_id' => $adminId,
                'views' => 88,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
        ];

        foreach ($posts as $post) {
            \App\Models\Post::updateOrCreate(
                ['slug' => $post['slug']],
                $post
            );
        }
    }
}
