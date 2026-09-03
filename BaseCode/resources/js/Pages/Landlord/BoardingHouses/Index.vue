<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { showConfirm, showError, showSuccess } from "@/Utils/swal";
import { watch } from "vue";
defineProps({
    boardingHouses: Array,
});

const selectHouse = (id) => {
    router.post(
        route("landlord.select-boarding-house"),
        { id },
        { preserveScroll: true },
    );
};

// const getStatusBadge = (status) => {
//     switch (status) {
//         case "approved":
//             return "bg-emerald-100 text-emerald-700";
//         case "pending":
//             return "bg-amber-100 text-amber-700";
//         case "rejected":
//             return "bg-rose-100 text-rose-700";
//         default:
//             return "bg-slate-100 text-slate-700";
//     }
// };
// const getStatusText = (status) => {
//     switch (status) {
//         case "approved":
//             return "Đã Duyệt";
//         case "pending":
//             return "Đang Chờ";
//         case "rejected":
//             return "Từ Chối";
//         default:
//             return "Không rõ";
//     }
// };

const deleteHouse = async (id) => {
    const confirm = await showConfirm(
        "Xác nhận xoá?",
        "Bạn có chắc muốn xoá cơ sở trọ này? Hành động này sẽ xoá toàn bộ phòng và dữ liệu liên quan và không thể hoàn tác",
        "Đồng ý xoá",
        "Huỷ bỏ",
    );
    if (confirm) {
        router.delete(route("landlord.boarding-houses.destroy", id), {
            preserveScroll: true,
            onError: (errors) => {
                showError("Lỗi", "Không thể xoá cơ sở này.");
            },
        });
    }
};

const page = usePage();
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            showSuccess("Thành công", flash.success);
        }
        if (flash?.error) {
            showError("Thất bại", flash.error);
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>

    <Head title="Quản Lý Cơ Sở" />
    <LandlordLayout>
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">
                    Quản Lý Cơ Sở
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Quản lý danh sách các khu trọ / homestay của bạn
                </p>
            </div>
            <Link href="/landlord/boarding-houses/create"
                class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all shadow-md shadow-emerald-600/20 flex items-center justify-center gap-2">
                <i class="bi bi-plus-lg"></i> Thêm Cơ Sở Mới
            </Link>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div v-if="!boardingHouses.length" class="px-5 py-12 text-center text-slate-500">
                <i class="bi bi-buildings text-5xl text-slate-300 mb-3 block"></i>
                <p class="font-bold text-sm text-slate-700 mb-1">Bạn chưa có cơ sở trọ nào.</p>
                <p class="text-xs text-slate-500">Hãy thêm cơ sở mới để bắt đầu quản lý phòng và hợp đồng.</p>
            </div>
            <div v-else>
                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500">
                                <th class="px-5 py-4 text-xs font-bold uppercase tracking-wider w-16">
                                    ID
                                </th>
                                <th class="px-5 py-4 text-xs font-bold uppercase tracking-wider">
                                    Tên Cơ Sở
                                </th>
                                <th class="px-5 py-4 text-xs font-bold uppercase tracking-wider">
                                    Địa Chỉ
                                </th>
                                <th class="px-5 py-4 text-xs font-bold uppercase tracking-wider text-right">
                                    Thao Tác
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="house in boardingHouses" :key="house.id"
                                class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-5 py-4 text-sm font-bold text-slate-500">
                                    #{{ house.id }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-800 flex items-center gap-2">
                                        <span>{{ house.name }}</span>
                                        <span v-if="$page.props.auth.selected_boarding_house_id === house.id"
                                            class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] rounded-full uppercase font-black border border-emerald-200/80 inline-flex items-center gap-1">
                                            <i class="bi bi-geo-alt-fill"></i> Đang Chọn
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-xs sm:text-sm text-slate-600 font-medium">
                                        <i class="bi bi-geo-alt text-slate-400 mr-1"></i>
                                        {{ house.address_detail }}, {{ house.district }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <button v-if="$page.props.auth.selected_boarding_house_id !== house.id" 
                                            @click="selectHouse(house.id)"
                                            class="text-xs font-bold bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 px-3 py-1.5 rounded-lg transition-all inline-flex items-center gap-1.5">
                                            <i class="bi bi-box-arrow-in-right"></i> Chọn cơ sở
                                        </button>
                                        <Link :href="route('landlord.boarding-houses.show', house.id)" 
                                            class="text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-lg transition-all inline-flex items-center gap-1.5" 
                                            title="Xem chi tiết">
                                            <i class="bi bi-eye"></i> Chi tiết
                                        </Link>
                                        <Link :href="route('landlord.boarding-houses.edit', house.id)" 
                                            class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all" 
                                            title="Chỉnh sửa">
                                            <i class="bi bi-pencil-square text-base"></i>
                                        </Link>
                                        <button @click="deleteHouse(house.id)"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all" 
                                            title="Xóa cơ sở">
                                            <i class="bi bi-trash text-base"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards View (Single Clean Card List) -->
                <div class="block md:hidden divide-y divide-slate-100">
                    <div v-for="house in boardingHouses" :key="house.id"
                        class="p-4 space-y-3 hover:bg-slate-50/60 transition-colors">
                        <!-- Card Header -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-slate-400">#{{ house.id }}</span>
                                <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2 flex-wrap">
                                    {{ house.name }}
                                    <span v-if="$page.props.auth.selected_boarding_house_id === house.id"
                                        class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[9px] rounded-full uppercase font-black border border-emerald-200">
                                        <i class="bi bi-geo-alt-fill"></i> Đang Chọn
                                    </span>
                                </h4>
                            </div>
                            <!-- Card Quick Actions -->
                            <div class="flex items-center gap-1">
                                <Link :href="route('landlord.boarding-houses.show', house.id)" 
                                    class="p-1.5 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Xem chi tiết">
                                    <i class="bi bi-eye text-base"></i>
                                </Link>
                                <Link :href="route('landlord.boarding-houses.edit', house.id)" 
                                    class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Chỉnh sửa">
                                    <i class="bi bi-pencil-square text-base"></i>
                                </Link>
                                <button @click="deleteHouse(house.id)"
                                    class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Xóa">
                                    <i class="bi bi-trash text-base"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Card Address -->
                        <div class="text-xs text-slate-600 flex items-start gap-1.5 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <i class="bi bi-geo-alt-fill text-emerald-600 mt-0.5 shrink-0"></i>
                            <span>{{ house.address_detail }}, {{ house.district }}</span>
                        </div>

                        <!-- Select House Button for Mobile -->
                        <div v-if="$page.props.auth.selected_boarding_house_id !== house.id" class="pt-1">
                            <button @click="selectHouse(house.id)"
                                class="w-full bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5">
                                <i class="bi bi-box-arrow-in-right"></i> Chọn cơ sở này
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
