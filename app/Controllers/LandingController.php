<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

final class LandingController extends Controller
{
    public function index(Request $request): void
    {
        if ($this->currentUser() !== null) {
            $this->redirect($this->roleHomePath());

            return;
        }

        $this->render('landing/index', [], null);
    }

    public function privacyPolicy(Request $request): void
    {
        $this->render('landing/info_page', [
            'metaTitle' => 'Chính Sách Bảo Mật | LivQuiz Learning',
            'activePage' => 'privacy',
            'pageLabel' => 'Thông tin pháp lý',
            'pageTitle' => 'Chính sách bảo mật',
            'pageDescription' => 'Chính sách này mô tả cách LivQuiz thu thập, sử dụng và bảo vệ dữ liệu người dùng trong quá trình vận hành hệ thống.',
            'sections' => [
                [
                    'title' => '1. Dữ liệu được thu thập',
                    'paragraphs' => [
                        'LivQuiz thu thập các thông tin phục vụ vận hành nền tảng: tên hiển thị, thư điện tử đăng nhập, vai trò tài khoản, lịch sử làm bài, kết quả chấm điểm và tài liệu do người dùng tải lên.',
                        'Chúng tôi không yêu cầu dữ liệu nhạy cảm ngoài phạm vi cần thiết cho mục đích học tập và quản lý bài kiểm tra.',
                    ],
                ],
                [
                    'title' => '2. Mục đích sử dụng dữ liệu',
                    'list' => [
                        'Xác thực tài khoản và duy trì phiên làm việc an toàn.',
                        'Tạo, lưu trữ, chấm điểm và hiển thị kết quả bài kiểm tra.',
                        'Cải thiện chất lượng hệ thống và hỗ trợ người dùng khi phát sinh sự cố.',
                    ],
                ],
                [
                    'title' => '3. Bảo mật và chia sẻ dữ liệu',
                    'paragraphs' => [
                        'Dữ liệu được lưu trên hạ tầng của hệ thống, có phân quyền truy cập theo vai trò và phạm vi nghiệp vụ.',
                        'LivQuiz không bán dữ liệu người dùng cho bên thứ ba. Việc cung cấp dữ liệu chỉ được thực hiện khi có yêu cầu pháp lý hợp lệ.',
                    ],
                ],
                [
                    'title' => '4. Quyền của người dùng',
                    'list' => [
                        'Yêu cầu kiểm tra thông tin tài khoản đã lưu.',
                        'Yêu cầu chỉnh sửa dữ liệu không chính xác.',
                        'Yêu cầu xóa dữ liệu khi ngừng sử dụng dịch vụ (trừ phần bắt buộc lưu theo quy định).',
                    ],
                ],
            ],
        ], null);
    }

    public function termsOfUse(Request $request): void
    {
        $this->render('landing/info_page', [
            'metaTitle' => 'Điều Khoản Sử Dụng | LivQuiz Learning',
            'activePage' => 'terms',
            'pageLabel' => 'Điều khoản',
            'pageTitle' => 'Điều khoản sử dụng',
            'pageDescription' => 'Khi truy cập và sử dụng LivQuiz, người dùng đồng ý tuân thủ các điều khoản dưới đây để đảm bảo môi trường học tập ổn định và công bằng.',
            'sections' => [
                [
                    'title' => '1. Quy định tài khoản',
                    'list' => [
                        'Người dùng chịu trách nhiệm bảo mật thông tin đăng nhập.',
                        'Không mạo danh cá nhân/tổ chức khác khi tạo tài khoản.',
                        'Mỗi hành vi phát sinh từ tài khoản được xem là do chủ tài khoản chịu trách nhiệm.',
                    ],
                ],
                [
                    'title' => '2. Quy định nội dung',
                    'list' => [
                        'Không đăng tải nội dung vi phạm pháp luật, đạo đức, bản quyền hoặc chứa mã độc.',
                        'Người tạo bài kiểm tra chịu trách nhiệm về độ chính xác của câu hỏi và đáp án.',
                        'Nghiêm cấm lợi dụng lỗ hổng hệ thống để gian lận hoặc làm gián đoạn dịch vụ.',
                    ],
                ],
                [
                    'title' => '3. Quyền của hệ thống LivQuiz',
                    'paragraphs' => [
                        'LivQuiz có quyền tạm khóa hoặc vô hiệu hóa tài khoản vi phạm điều khoản.',
                        'LivQuiz có thể điều chỉnh tính năng, giao diện hoặc chính sách vận hành để nâng cao chất lượng dịch vụ.',
                    ],
                ],
                [
                    'title' => '4. Giới hạn trách nhiệm',
                    'paragraphs' => [
                        'LivQuiz nỗ lực duy trì tính ổn định nhưng không cam kết dịch vụ không gián đoạn tuyệt đối trong mọi thời điểm.',
                        'Người dùng cần tự sao lưu các nội dung quan trọng trong quá trình sử dụng.',
                    ],
                ],
            ],
        ], null);
    }

    public function helpCenter(Request $request): void
    {
        $this->render('landing/info_page', [
            'metaTitle' => 'Trung Tâm Trợ Giúp | LivQuiz Learning',
            'activePage' => 'help',
            'pageLabel' => 'Hỗ trợ',
            'pageTitle' => 'Trung tâm trợ giúp',
            'pageDescription' => 'Hướng dẫn nhanh cho các tác vụ phổ biến trên LivQuiz.',
            'sections' => [
                [
                    'title' => '1. Tạo đề từ tài liệu',
                    'paragraphs' => [
                        'Bước 1: vào mục Tải lên để tải tệp `.txt`, `.docx` hoặc `.pdf`.',
                        'Bước 2: vào Xem trước/Sửa hoặc Tạo đề thủ công để nhập nội dung và xem trước danh sách câu hỏi.',
                        'Bước 3: chỉnh sửa đáp án đúng, thêm/xóa câu hỏi nếu cần, sau đó lưu bài kiểm tra.',
                    ],
                ],
                [
                    'title' => '2. Cơ chế AI trên LivQuiz',
                    'paragraphs' => [
                        'AI chỉ dùng để gợi ý thêm câu hỏi ở bước xem trước.',
                        'AI không tự động thay thế dữ liệu đã nhập và không tự lưu ngẫu nhiên vào đề thi.',
                    ],
                ],
                [
                    'title' => '3. Không thấy dữ liệu nộp bài',
                    'paragraphs' => [
                        'Kiểm tra mục Kết quả trong tài khoản của bạn.',
                        'Nếu chưa thấy, gửi mã bài kiểm tra và thời điểm nộp cho bộ phận hỗ trợ để kiểm tra log hệ thống.',
                    ],
                ],
                [
                    'title' => '4. Lỗi PDF không đọc được',
                    'paragraphs' => [
                        'Máy chủ cần có công cụ `pdftotext` để trích xuất nội dung PDF.',
                        'Nếu thiếu công cụ này, hãy dùng file `.docx`/`.txt` hoặc liên hệ quản trị viên để bật hỗ trợ PDF.',
                    ],
                ],
            ],
        ], null);
    }

    public function contact(Request $request): void
    {
        $this->render('landing/info_page', [
            'metaTitle' => 'Liên Hệ | LivQuiz Learning',
            'activePage' => 'contact',
            'pageLabel' => 'Kết nối',
            'pageTitle' => 'Liên hệ',
            'pageDescription' => 'Liên hệ với đội vận hành LivQuiz khi cần hỗ trợ kỹ thuật, báo lỗi hoặc góp ý phát triển.',
            'supportHours' => 'Thứ 2 – Thứ 6, 08:30 – 17:30 (GMT+7)',
            'contactChannels' => [
                [
                    'icon' => 'support_agent',
                    'title' => 'Thư hỗ trợ',
                    'email' => 'support@livquiz.local',
                    'description' => 'Báo lỗi, câu hỏi sử dụng và yêu cầu kỹ thuật.',
                ],
                [
                    'icon' => 'domain',
                    'title' => 'Thư vận hành',
                    'email' => 'ops@livquiz.local',
                    'description' => 'Phản hồi chung, góp ý sản phẩm và hợp tác.',
                ],
            ],
            'sections' => [
                [
                    'title' => 'Nội dung nên gửi khi báo lỗi',
                    'list' => [
                        'Mô tả ngắn gọn lỗi gặp phải.',
                        'URL hoặc màn hình thao tác xảy ra lỗi.',
                        'Thời điểm xảy ra lỗi và ảnh chụp minh họa (nếu có).',
                    ],
                ],
                [
                    'title' => 'Mẫu tiêu đề email đề xuất',
                    'paragraphs' => [
                        '[LIVQUIZ HỖ TRỢ] Mô tả vấn đề ngắn gọn',
                    ],
                ],
            ],
        ], null);
    }
}
