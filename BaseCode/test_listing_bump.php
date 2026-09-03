<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Room;
use App\Models\RoomPost;
use App\Models\Property;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== START TESTING BUMP & INTERLEAVING ===\n";

DB::beginTransaction();

try {
    // 1. Tạo 2 chủ trọ mẫu
    $landlordA = User::create([
        'name' => 'Landlord A Test',
        'email' => 'landlordA_test_' . uniqid() . '@example.com',
        'phone' => '0987654321',
        'password' => bcrypt('password'),
        'role' => 'landlord',
        'status' => 'active',
        'email_verified_at' => now(),
    ]);

    $landlordB = User::create([
        'name' => 'Landlord B Test',
        'email' => 'landlordB_test_' . uniqid() . '@example.com',
        'phone' => '0987654322',
        'password' => bcrypt('password'),
        'role' => 'landlord',
        'status' => 'active',
        'email_verified_at' => now(),
    ]);

    echo "Created 2 test landlords:\n";
    echo "- Landlord A ID: {$landlordA->id}\n";
    echo "- Landlord B ID: {$landlordB->id}\n\n";

    // 2. Mua gói đẩy tin (giả lập) cho 2 chủ trọ
    $landlordA->update([
        'bump_credits' => 30,
        'package_name' => 'Gói Phổ thông (30 lượt)'
    ]);

    $landlordB->update([
        'bump_credits' => 10,
        'package_name' => 'Gói Cơ bản (10 lượt)'
    ]);

    echo "Simulated Package Purchase:\n";
    echo "- Landlord A: {$landlordA->package_name}, Credits: {$landlordA->bump_credits}\n";
    echo "- Landlord B: {$landlordB->package_name}, Credits: {$landlordB->bump_credits}\n\n";

    // Lấy hoặc tạo property mẫu
    $property = Property::first();
    if (!$property) {
        $property = Property::create([
            'user_id' => $landlordA->id,
            'name' => 'Boarding House Test',
            'address' => 'Ninh Binh',
            'status' => 'active',
        ]);
    }

    // Lấy 1 room mẫu hoặc tạo mới để gắn vào bài đăng
    $room = Room::first();
    if (!$room) {
        $room = Room::create([
            'property_id' => $property->id,
            'room_number' => '101',
            'price' => 2000000,
            'area' => 25,
            'capacity' => 2,
            'status' => 'available',
        ]);
    }

    // 3. Tạo các bài đăng
    // Chủ trọ A: 3 bài đăng đã được duyệt
    $postA1 = RoomPost::create([
        'landlord_id' => $landlordA->id,
        'room_id' => $room->id,
        'title' => 'Tin đăng A1 của Chủ trọ A',
        'description' => 'Mô tả A1',
        'status' => 'approved',
        'published_at' => now()->subMinutes(10),
    ]);

    $postA2 = RoomPost::create([
        'landlord_id' => $landlordA->id,
        'room_id' => $room->id,
        'title' => 'Tin đăng A2 của Chủ trọ A',
        'description' => 'Mô tả A2',
        'status' => 'approved',
        'published_at' => now()->subMinutes(9),
    ]);

    $postA3 = RoomPost::create([
        'landlord_id' => $landlordA->id,
        'room_id' => $room->id,
        'title' => 'Tin đăng A3 của Chủ trọ A',
        'description' => 'Mô tả A3',
        'status' => 'approved',
        'published_at' => now()->subMinutes(8),
    ]);

    // Chủ trọ B: 2 bài đăng đã được duyệt
    $postB1 = RoomPost::create([
        'landlord_id' => $landlordB->id,
        'room_id' => $room->id,
        'title' => 'Tin đăng B1 của Chủ trọ B',
        'description' => 'Mô tả B1',
        'status' => 'approved',
        'published_at' => now()->subMinutes(7),
    ]);

    $postB2 = RoomPost::create([
        'landlord_id' => $landlordB->id,
        'room_id' => $room->id,
        'title' => 'Tin đăng B2 của Chủ trọ B',
        'description' => 'Mô tả B2',
        'status' => 'approved',
        'published_at' => now()->subMinutes(6),
    ]);

    // 1 Tin thường của chủ trọ khác (không được đẩy)
    $postNormal = RoomPost::create([
        'landlord_id' => $landlordA->id,
        'room_id' => $room->id,
        'title' => 'Tin thường (Không đẩy)',
        'description' => 'Mô tả tin thường',
        'status' => 'approved',
        'published_at' => now()->subMinutes(5),
    ]);

    echo "Created 5 test posts and 1 normal post.\n\n";

    // 4. Thực hiện đẩy tin (bumping)
    // Đẩy A1, A2, A3
    $postA1->update(['bumped_at' => now()->subSeconds(50), 'bump_count' => 1]);
    $landlordA->decrement('bump_credits');

    $postA2->update(['bumped_at' => now()->subSeconds(40), 'bump_count' => 1]);
    $landlordA->decrement('bump_credits');

    $postA3->update(['bumped_at' => now()->subSeconds(30), 'bump_count' => 1]);
    $landlordA->decrement('bump_credits');

    // Đẩy B1, B2
    $postB1->update(['bumped_at' => now()->subSeconds(20), 'bump_count' => 1]);
    $landlordB->decrement('bump_credits');

    $postB2->update(['bumped_at' => now()->subSeconds(10), 'bump_count' => 1]);
    $landlordB->decrement('bump_credits');

    echo "Simulated bumping posts:\n";
    echo "- Landlord A remaining credits: {$landlordA->fresh()->bump_credits} (expected: 27)\n";
    echo "- Landlord B remaining credits: {$landlordB->fresh()->bump_credits} (expected: 8)\n\n";

    // 5. Test Thuật toán Interleaving (Xen kẽ hiển thị)
    $baseQuery = RoomPost::where('status', 'approved')
        ->whereIn('landlord_id', [$landlordA->id, $landlordB->id]);

    // Lấy tin đã đẩy
    $bumpedPosts = (clone $baseQuery)
        ->whereNotNull('bumped_at')
        ->orderBy('bumped_at', 'desc')
        ->get();

    // Lấy tin thường
    $regularPosts = (clone $baseQuery)
        ->whereNull('bumped_at')
        ->orderBy('published_at', 'desc')
        ->get();

    // Nhóm theo landlord
    $bumpedByLandlord = [];
    foreach ($bumpedPosts as $post) {
        $bumpedByLandlord[$post->landlord_id][] = $post;
    }

    // Trộn xen kẽ
    $interleavedBumped = [];
    $hasMore = true;
    $index = 0;
    while ($hasMore) {
        $hasMore = false;
        foreach ($bumpedByLandlord as $landlordId => $posts) {
            if (isset($posts[$index])) {
                $interleavedBumped[] = $posts[$index];
                $hasMore = true;
            }
        }
        $index++;
    }

    $allPosts = array_merge($interleavedBumped, $regularPosts->all());

    echo "=== ALGORITHM OUTPUT ORDER ===\n";
    foreach ($allPosts as $i => $post) {
        $type = $post->bumped_at ? "BUMPED (bumped_at: {$post->bumped_at})" : "NORMAL";
        echo ($i + 1) . ". Landlord ID: {$post->landlord_id} - Title: '{$post->title}' - Type: {$type}\n";
    }

    echo "\n=== VERIFYING ORDER ===\n";
    $success = true;
    
    // Kiểm tra xen kẽ giữa các vị trí đẩy tin liên tiếp
    for ($i = 0; $i < count($interleavedBumped) - 1; $i++) {
        $currentPost = $interleavedBumped[$i];
        $nextPost = $interleavedBumped[$i + 1];
        
        // Nếu 2 tin liên tiếp trùng landlord, kiểm tra xem có phải do một bên đã hết tin hay không
        if ($currentPost->landlord_id === $nextPost->landlord_id) {
            // Xem landlord còn lại có tin nào ở sau vị trí này không
            $otherLandlordId = $currentPost->landlord_id === $landlordA->id ? $landlordB->id : $landlordA->id;
            // Nếu landlord còn lại vẫn còn tin chưa hiển thị ở sau, việc trùng này là sai
            $hasRemainingForOther = false;
            for ($j = $i + 1; $j < count($interleavedBumped); $j++) {
                if ($interleavedBumped[$j]->landlord_id === $otherLandlordId) {
                    $hasRemainingForOther = true;
                    break;
                }
            }
            if ($hasRemainingForOther) {
                echo "❌ Error at index {$i}: Found consecutive posts from same landlord while other landlord still has posts.\n";
                $success = false;
            }
        }
    }

    // Đảm bảo tin thường luôn xếp sau tin đẩy
    $foundNormal = false;
    foreach ($allPosts as $post) {
        if ($post->bumped_at === null) {
            $foundNormal = true;
        } else {
            if ($foundNormal) {
                echo "❌ Error: Found a BUMPED post AFTER a NORMAL post.\n";
                $success = false;
            }
        }
    }

    if ($success) {
        echo "✅ SUCCESS: Interleaving sorting works perfectly! Landlords' bumped posts are evenly distributed.\n";
    } else {
        echo "❌ FAILURE: Sorting order did not match expected interleaving sequence.\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
} finally {
    DB::rollBack();
    echo "\nDatabase transaction rolled back. Clean test environment preserved.\n";
}
