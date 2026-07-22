<script setup>
import { ref, watch, computed } from "vue";

const props = defineProps({
    hideFloorSelect: Boolean,
    show: Boolean,
    isEdit: Boolean,
    room: Object,
    floorId: [Number, String],
    floorName: String,
    statusConfig: Object,
    existingRooms: { type: Array, default: () => [] },
    floors: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
});
const emit = defineEmits(["close", "submitted", "update:floorId"]);

const addMode = ref("single"); // 'single' | 'multiple'
const multipleCount = ref(1);

const form = ref({
    room_number: "",
    price: 3000000,
    area: 20,
    capacity: 2,
    status: "available",
    amenities: "",
    service_ids: [],
});
const submitting = ref(false);
const originalStatus = ref("available");

const displayPrice = computed({
    get() {
        if (form.value.price === null || form.value.price === undefined || form.value.price === '') return ''
        return new Intl.NumberFormat('en-US').format(form.value.price)
    },
    set(val) {
        const raw = String(val).replace(/\D/g, '')
        form.value.price = raw ? parseInt(raw, 10) : 0
    }
})

const capitalize = (str) =>
    str ? str.charAt(0).toUpperCase() + str.slice(1) : "";
const isInfoLocked = computed(
    () =>
        props.isEdit && ["deposited", "rented"].includes(originalStatus.value),
);

const colorsConfig = {
    amber: {
        text: "text-amber-500",
        bg: "bg-amber-50",
        border: "border-amber-200",
    },
    blue: {
        text: "text-blue-500",
        bg: "bg-blue-50",
        border: "border-blue-200",
    },
    cyan: {
        text: "text-cyan-500",
        bg: "bg-cyan-50",
        border: "border-cyan-200",
    },
    rose: {
        text: "text-rose-500",
        bg: "bg-rose-50",
        border: "border-rose-200",
    },
    purple: {
        text: "text-purple-500",
        bg: "bg-purple-50",
        border: "border-purple-200",
    },
    emerald: {
        text: "text-emerald-500",
        bg: "bg-emerald-50",
        border: "border-emerald-200",
    },
};

// Transitions for room statuses
const statusTransitions = {
    available: ["deposited", "maintenance"],
    deposited: ["rented", "available"],
    rented: ["expiring_soon", "maintenance"],
    expiring_soon: ["pending_renewal"],
    pending_renewal: ["rented", "available"],
    maintenance: ["available"],
    suspended: ["available"],
    under_construction: ["available"],
};

const allowedKeys = (currentSt) => {
    const allowed = statusTransitions[currentSt] || [];
    return [currentSt, ...allowed];
};

const errors = ref({});

watch(
    () => props.show,
    (v) => {
        if (!v) return;
        submitting.value = false;
        errors.value = {};

        if (props.isEdit && props.room) {
            addMode.value = "single";
            form.value = {
                room_number: props.room.name
                    ? props.room.name.replace(/\D/g, "")
                    : "",
                price: props.room.price || 3000000,
                area: props.room.area || 20,
                capacity: props.room.capacity || 2,
                status: props.room.status || "available",
                maintenance_reason: props.room.maintenance_reason || "",
                amenities: props.room.amenities || "",
                service_ids: props.room.services
                    ? props.room.services.map((s) => s.id)
                    : [],
            };
            originalStatus.value = props.room.status || "available";
        } else {
            addMode.value = "single";
            multipleCount.value = 1;
            form.value = {
                room_number: "",
                price: 3000000,
                area: 20,
                capacity: 2,
                status: "available",
                maintenance_reason: "",
                amenities: "",
                service_ids: [],
            };
        }
    },
);

const validate = () => {
    errors.value = {};
    if (addMode.value === "single") {
        const num = form.value.room_number
            ? form.value.room_number.toString().trim()
            : "";
        form.value.room_number = num;
        if (!num) {
            errors.value.room_number = "Vui lòng nhập số phòng";
            return false;
        }

        const name = `P.${num}`;

        if (props.floorName) {
            const floorMatch = props.floorName.match(/\d+/);
            if (floorMatch) {
                const prefix = floorMatch[0];
                if (!num.startsWith(prefix)) {
                    errors.value.room_number = `Phòng thuộc "${props.floorName}" phải bắt đầu bằng số ${prefix} (VD: ${prefix}01)`;
                    return false;
                }
            }
        }

        const isDuplicate = props.floors.some((f) =>
            f.rooms.some((r) => {
                if (props.isEdit && props.room && r.id === props.room.id)
                    return false;
                return r.name.trim().toLowerCase() === name.toLowerCase();
            }),
        );
        if (isDuplicate) {
            errors.value.room_number = `Số phòng "${name}" đã tồn tại`;
            return false;
        }
    } else {
        if (!multipleCount.value || multipleCount.value < 1) {
            errors.value.multipleCount = "Số lượng phòng phải lớn hơn 0";
            return false;
        }
        if (multipleCount.value > 50) {
            errors.value.multipleCount =
                "Chỉ có thể tạo tối đa 50 phòng cùng lúc";
            return false;
        }
    }
    if (!form.value.price || form.value.price <= 0) {
        errors.value.price = "Giá thuê phải lớn hơn 0";
        return false;
    }
    if (!form.value.area || form.value.area <= 0) {
        errors.value.area = "Diện tích phải lớn hơn 0";
        return false;
    }
    if (!form.value.capacity || form.value.capacity <= 0) {
        errors.value.capacity = "Sức chứa phải lớn hơn 0";
        return false;
    }
    if (
        form.value.status === "maintenance" &&
        !form.value.maintenance_reason.trim()
    ) {
        errors.value.maintenance_reason = "Vui lòng nhập lý do bảo trì";
        return false;
    }
    return true;
};

const submit = () => {
    if (!validate() || submitting.value) return;
    submitting.value = true;

    const fd = new FormData();
    fd.append("floor_id", props.floorId);
    fd.append("price", form.value.price);
    fd.append("area", form.value.area);
    fd.append("capacity", form.value.capacity);
    fd.append("status", form.value.status);
    if (form.value.status === "maintenance" && form.value.maintenance_reason)
        fd.append("maintenance_reason", form.value.maintenance_reason);
    if (form.value.amenities) fd.append("amenities", form.value.amenities);

    form.value.service_ids.forEach((id) => {
        fd.append("service_ids[]", id);
    });

    if (addMode.value === "single") {
        fd.append("room_number", `P.${form.value.room_number}`);
    } else {
        // Calculate multiple room names
        const floorMatch = props.floorName
            ? props.floorName.match(/\d+/)
            : null;
        const prefixStr = floorMatch ? floorMatch[0] : "1";
        const prefixNum = parseInt(prefixStr);

        let maxRoomNum = prefixNum * 100; // e.g. 200
        props.existingRooms.forEach((r) => {
            const match = r.room_number ? r.room_number.match(/\d+/) : null;
            if (match) {
                const num = parseInt(match[0]);
                if (num > maxRoomNum && r.room_number.includes(prefixStr)) {
                    maxRoomNum = num;
                }
            }
        });

        const generatedNames = [];
        for (let i = 1; i <= multipleCount.value; i++) {
            generatedNames.push(`P.${maxRoomNum + i}`);
        }

        generatedNames.forEach((name) => {
            fd.append("room_numbers[]", name);
        });
    }

    emit("submitted", fd);
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click.self="emit('close')">
        <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                <h3 class="text-sm font-bold text-slate-800">
                    {{ isEdit ? "Sửa Phòng" : "Thêm Phòng Mới" }}
                </h3>
                <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4 overflow-y-auto flex-1">
                <!-- Mode Toggle (Chỉ khi thêm mới) -->
                <div v-if="!isEdit" class="flex items-center bg-slate-50 p-1 rounded-xl w-fit border border-slate-100">
                    <button @click="addMode = 'single'" :class="[
                        'px-4 py-1.5 rounded-lg text-xs font-bold transition-all',
                        addMode === 'single'
                            ? 'bg-white text-emerald-600 shadow-sm'
                            : 'text-slate-400 hover:text-slate-600',
                    ]">
                        Thêm 1 phòng
                    </button>
                    <button @click="addMode = 'multiple'" :class="[
                        'px-4 py-1.5 rounded-lg text-xs font-bold transition-all',
                        addMode === 'multiple'
                            ? 'bg-white text-emerald-600 shadow-sm'
                            : 'text-slate-400 hover:text-slate-600',
                    ]">
                        Thêm nhiều phòng
                    </button>
                </div>

                <!-- Chọn Tầng (chỉ hiển thị khi thêm mới phòng) -->
                <div v-if="!isEdit && !hideFloorSelect" class="space-y-1">
                    <label class="text-xs font-bold text-slate-500">Tầng <span class="text-rose-500">*</span></label>
                    <select :value="floorId" @change="
                        emit(
                            'update:floorId',
                            parseInt($event.target.value),
                        )
                        "
                        class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none transition-all cursor-pointer bg-white">
                        <option v-for="f in floors" :key="f.id" :value="f.id">
                            {{ f.name }}
                        </option>
                    </select>
                </div>

                <!-- Room Number (Single mode) -->
                <div v-if="addMode === 'single'" class="space-y-1">
                    <label class="text-xs font-bold text-slate-500">Số phòng
                        <span v-if="!isEdit" class="text-rose-500">*</span></label>

                    <div v-if="isEdit"
                        class="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 rounded-xl text-xs font-bold text-slate-500 cursor-not-allowed">
                        P.{{ form.room_number }}
                    </div>

                    <template v-else>
                        <input v-model.number="form.room_number" type="number" min="1" :class="[
                            'w-full px-3.5 py-2.5 border rounded-xl text-xs font-medium outline-none transition-all',
                            errors.room_number
                                ? 'border-rose-300 bg-rose-50/50 focus:border-rose-500'
                                : 'border-slate-200 focus:border-emerald-500',
                        ]" placeholder="VD: 101, 102, 103, ..." @input="errors.room_number = ''" />
                        <span v-if="errors.room_number"
                            class="text-[10px] text-rose-500 font-semibold flex items-center gap-1 mt-1">
                            <i class="bi bi-exclamation-circle"></i>
                            {{ errors.room_number }}
                        </span>
                    </template>
                </div>

                <!-- Multiple Count (Multiple mode) -->
                <div v-if="addMode === 'multiple'" class="space-y-1">
                    <label class="text-xs font-bold text-slate-500">Số lượng phòng cần tạo
                        <span class="text-rose-500">*</span></label>
                    <input v-model.number="multipleCount" type="number" min="1" max="50" :class="[
                        'w-full px-3.5 py-2.5 border rounded-xl text-xs font-medium outline-none transition-all',
                        errors.multipleCount
                            ? 'border-rose-300 bg-rose-50/50 focus:border-rose-500'
                            : 'border-slate-200 focus:border-emerald-500',
                    ]" @input="errors.multipleCount = ''" />
                    <span v-if="errors.multipleCount"
                        class="text-[10px] text-rose-500 font-semibold flex items-center gap-1 mt-1">
                        <i class="bi bi-exclamation-circle"></i>
                        {{ errors.multipleCount }}
                    </span>
                    <p class="text-[10px] text-slate-400 mt-1">
                        Hệ thống sẽ tự động sinh tên phòng theo thứ tự tiếp theo
                        của tầng này (VD: P.201, P.202...)
                    </p>
                </div>

                <!-- Price, Area, and Capacity Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex flex-col justify-end space-y-1">
                        <label class="text-xs font-bold text-slate-500">Giá thuê (VNĐ/tháng)
                            <span v-if="!isInfoLocked" class="text-rose-500">*</span></label>
                        <div v-if="isInfoLocked"
                            class="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 rounded-xl text-xs font-bold text-slate-500 cursor-not-allowed">
                            {{
                                new Intl.NumberFormat("vi-VN").format(
                                    form.price,
                                )
                            }}
                            đ
                        </div>
                        <template v-else>
                            <input v-model="displayPrice" type="text" placeholder="0" :class="[
                                'w-full px-3.5 py-2.5 border rounded-xl text-xs font-bold text-slate-700 outline-none transition-all',
                                errors.price
                                    ? 'border-rose-300 bg-rose-50/50 focus:border-rose-500'
                                    : 'border-slate-200 focus:border-emerald-500',
                            ]" @input="errors.price = ''" />
                            <span v-if="errors.price"
                                class="text-[10px] text-rose-500 font-semibold flex items-center gap-1 mt-1">
                                <i class="bi bi-exclamation-circle"></i>
                                {{ errors.price }}
                            </span>
                        </template>
                    </div>

                    <div class="flex flex-col justify-end space-y-1">
                        <label class="text-xs font-bold text-slate-500">Diện tích (m²)
                            <span v-if="!isEdit || form.status === 'maintenance'" class="text-rose-500">*</span></label>

                        <div v-if="isEdit && form.status !== 'maintenance'"
                            class="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 rounded-xl text-xs font-bold text-slate-500 cursor-not-allowed">
                            {{ form.area }} m²
                        </div>

                        <template v-else>
                            <input v-model.number="form.area" type="number" :class="[
                                'w-full px-3.5 py-2.5 border rounded-xl text-xs font-medium outline-none transition-all',
                                errors.area
                                    ? 'border-rose-300 bg-rose-50/50 focus:border-rose-500'
                                    : 'border-slate-200 focus:border-emerald-500',
                            ]" @input="errors.area = ''" />
                            <span v-if="errors.area"
                                class="text-[10px] text-rose-500 font-semibold flex items-center gap-1 mt-1">
                                <i class="bi bi-exclamation-circle"></i>
                                {{ errors.area }}
                            </span>
                        </template>
                    </div>

                    <div class="flex flex-col justify-end space-y-1">
                        <label class="text-xs font-bold text-slate-500">Sức chứa (người)
                            <span v-if="!isEdit || form.status === 'maintenance'" class="text-rose-500">*</span></label>

                        <div v-if="isEdit && form.status !== 'maintenance'"
                            class="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 rounded-xl text-xs font-bold text-slate-500 cursor-not-allowed">
                            {{ form.capacity }} người
                        </div>

                        <template v-else>
                            <input v-model.number="form.capacity" type="number" min="1" :class="[
                                'w-full px-3.5 py-2.5 border rounded-xl text-xs font-medium outline-none transition-all',
                                errors.capacity
                                    ? 'border-rose-300 bg-rose-50/50 focus:border-rose-500'
                                    : 'border-slate-200 focus:border-emerald-500',
                            ]" @input="errors.capacity = ''" />
                            <span v-if="errors.capacity"
                                class="text-[10px] text-rose-500 font-semibold flex items-center gap-1 mt-1">
                                <i class="bi bi-exclamation-circle"></i>
                                {{ errors.capacity }}
                            </span>
                        </template>
                    </div>

                </div>

                <!-- Status Select Grid -->
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500">Trạng thái phòng
                        <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <label v-for="(cfg, key) in statusConfig" :key="key" v-show="isEdit
                            ? allowedKeys(originalStatus).includes(key)
                            : [
                                'available',
                                'under_construction',
                            ].includes(key)
                            " :class="[
                                'flex items-center gap-2 p-2.5 border rounded-xl text-[11px] font-bold cursor-pointer transition-all',
                                form.status === key
                                    ? 'bg-emerald-50 border-emerald-500 text-emerald-600 shadow-sm shadow-emerald-500/10'
                                    : 'border-slate-200 text-slate-600 hover:bg-slate-50',
                            ]">
                            <input type="radio" :value="key" v-model="form.status" class="hidden" />
                            <i :class="[
                                'bi',
                                cfg.icon,
                                form.status === key
                                    ? 'text-emerald-500'
                                    : 'text-slate-400',
                            ]"></i>
                            <span>{{ cfg.label }}</span>
                        </label>
                    </div>
                </div>

                <!-- Services Checkbox Grid -->
                <div v-if="services.length > 0" class="space-y-1">
                    <label class="text-xs font-bold text-slate-500">Dịch vụ áp dụng
                        <span class="text-slate-400 font-normal">(không bắt buộc)</span></label>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <label v-for="srv in services" :key="srv.id" :class="[
                            'flex items-center gap-2 p-2.5 border rounded-xl text-[11px] font-bold cursor-pointer transition-all',
                            form.service_ids.includes(srv.id)
                                ? [
                                    colorsConfig[srv.color || 'emerald']
                                        ?.bg,
                                    colorsConfig[srv.color || 'emerald']
                                        ?.border,
                                    colorsConfig[srv.color || 'emerald']
                                        ?.text,
                                    'shadow-sm',
                                    'border-opacity-50',
                                ]
                                : 'border-slate-200 text-slate-600 hover:bg-slate-50',
                        ]">
                            <input type="checkbox" :value="srv.id" v-model="form.service_ids" class="hidden" />
                            <i :class="[
                                'bi',
                                srv.icon || 'bi-lightning-charge-fill',
                                form.service_ids.includes(srv.id)
                                    ? colorsConfig[srv.color || 'emerald']
                                        ?.text
                                    : 'text-slate-400',
                            ]"></i>
                            <div class="flex flex-col">
                                <span>{{ srv.name }}</span>
                                <span :class="[
                                    'text-[9px] font-normal',
                                    form.service_ids.includes(srv.id)
                                        ? colorsConfig[
                                            srv.color || 'emerald'
                                        ]?.text
                                        : 'text-slate-400',
                                ]">{{
                                    new Intl.NumberFormat("vi-VN").format(
                                        srv.price,
                                    )
                                }}đ</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Maintenance Reason -->
                <div v-if="form.status === 'maintenance'" class="space-y-1">
                    <label class="text-xs font-bold text-slate-500">Lý do bảo trì
                        <span class="text-rose-500">*</span></label>
                    <input v-model="form.maintenance_reason" :class="[
                        'w-full px-3.5 py-2.5 border rounded-xl text-xs font-medium outline-none transition-all',
                        errors.maintenance_reason
                            ? 'border-rose-300 bg-rose-50/50 focus:border-rose-500'
                            : 'border-slate-200 focus:border-emerald-500',
                    ]" placeholder="VD: Sửa ống nước, sơn lại tường..." @input="errors.maintenance_reason = ''" />
                    <span v-if="errors.maintenance_reason"
                        class="text-[10px] text-rose-500 font-semibold flex items-center gap-1 mt-1">
                        <i class="bi bi-exclamation-circle"></i>
                        {{ errors.maintenance_reason }}
                    </span>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50/50">
                <button
                    class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors"
                    @click="emit('close')">
                    Hủy
                </button>
                <button
                    class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="submit" :disabled="submitting">
                    <i :class="isEdit ? 'bi bi-check-lg' : 'bi-plus-lg'"></i>
                    <span>{{
                        submitting
                            ? "Đang xử lý..."
                            : isEdit
                                ? "Lưu Thay Đổi"
                                : "Thêm Phòng"
                    }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
