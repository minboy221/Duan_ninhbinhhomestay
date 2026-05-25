<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'

const landlords = ref([
    { id:1, name:'Trần Văn Hùng', email:'vanhung@gmail.com', phone:'0912345678', cccd:'123456789012', rooms:5, plan:'Trả phí', verified:true,  joined:'10/01/2026' },
    { id:2, name:'Lê Thị Nga',    email:'thinga@gmail.com',  phone:'0987654321', cccd:'987654321012', rooms:3, plan:'Miễn phí', verified:false, joined:'15/02/2026' },
    { id:3, name:'Nguyễn Văn Bá', email:'vanba@gmail.com',   phone:'0909090909', cccd:'111222333444', rooms:8, plan:'Trả phí', verified:true,  joined:'01/03/2026' },
    { id:4, name:'Phạm Thị Mai',  email:'thimai@gmail.com',  phone:'0933111222', cccd:'555666777888', rooms:2, plan:'Miễn phí', verified:false, joined:'20/04/2026' },
])

const showDetail = ref(false)
const selected   = ref(null)

function open(l) { selected.value = l; showDetail.value = true }
function approve(l) { l.verified = true; showDetail.value = false }
</script>

<template>
    <Head title="Admin - Quản Lý Chủ Trọ" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Quản Lý Tài Khoản Chủ Trọ</h1>
                <p class="page-sub">Kiểm tra thông tin và duyệt xác minh chủ trọ</p>
            </div>
        </template>

        <div class="stats-row">
            <div class="scard"><i class="bi bi-house-check-fill" style="color:#7c3aed"></i><div><p class="snum">{{ landlords.length }}</p><p class="slbl">Tổng chủ trọ</p></div></div>
            <div class="scard"><i class="bi bi-patch-check-fill" style="color:#22c55e"></i><div><p class="snum">{{ landlords.filter(l=>l.verified).length }}</p><p class="slbl">Đã xác minh</p></div></div>
            <div class="scard"><i class="bi bi-clock-fill" style="color:#f97316"></i><div><p class="snum">{{ landlords.filter(l=>!l.verified).length }}</p><p class="slbl">Chờ xác minh</p></div></div>
        </div>

        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr><th>#</th><th>Chủ trọ</th><th>Số điện thoại</th><th>Số phòng</th><th>Gói dịch vụ</th><th>Xác minh</th><th style="text-align:center">Hành động</th></tr>
                </thead>
                <tbody>
                    <tr v-for="(l, i) in landlords" :key="l.id" class="trow">
                        <td class="idx">{{ i+1 }}</td>
                        <td>
                            <div class="user-cell">
                                <div class="ava" :style="`background:hsl(${l.id*80}deg,60%,55%)`">{{ l.name[0] }}</div>
                                <div><p class="fw">{{ l.name }}</p><p class="sm">{{ l.email }}</p></div>
                            </div>
                        </td>
                        <td class="sm">{{ l.phone }}</td>
                        <td><span class="room-badge">{{ l.rooms }} phòng</span></td>
                        <td><span :class="['plan-badge', l.plan==='Trả phí' ? 'plan-paid' : 'plan-free']">{{ l.plan }}</span></td>
                        <td>
                            <span :class="['ver-badge', l.verified ? 'ver-ok' : 'ver-pending']">
                                <i :class="['bi', l.verified ? 'bi-patch-check-fill' : 'bi-hourglass-split']"></i>
                                {{ l.verified ? 'Đã xác minh' : 'Chờ duyệt' }}
                            </span>
                        </td>
                        <td style="text-align:center">
                            <button @click="open(l)" class="act-btn act-primary">
                                <i class="bi bi-eye"></i> Xem
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Teleport to="body">
            <div v-if="showDetail" class="modal-overlay" @click.self="showDetail=false">
                <div class="modal-box">
                    <div class="modal-header">
                        <h3>Thông Tin Chủ Trọ</h3>
                        <button @click="showDetail=false" class="modal-close"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body">
                        <div class="ll-avatar">{{ selected?.name[0] }}</div>
                        <h4 class="ll-name">{{ selected?.name }}</h4>
                        <p class="ll-email">{{ selected?.email }}</p>
                        <div class="info-block">
                            <div class="ib-row"><span class="ib-l">Số ĐT</span><span class="ib-v">{{ selected?.phone }}</span></div>
                            <div class="ib-row"><span class="ib-l">CCCD</span><span class="ib-v">{{ selected?.cccd }}</span></div>
                            <div class="ib-row"><span class="ib-l">Số phòng</span><span class="ib-v">{{ selected?.rooms }} phòng</span></div>
                            <div class="ib-row"><span class="ib-l">Gói</span><span class="ib-v">{{ selected?.plan }}</span></div>
                            <div class="ib-row"><span class="ib-l">Tham gia</span><span class="ib-v">{{ selected?.joined }}</span></div>
                        </div>
                        <div class="cccd-preview">
                            <i class="bi bi-card-image"></i>
                            <span>Ảnh CCCD chưa có</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button @click="showDetail=false" class="btn-cancel">Đóng</button>
                        <button v-if="!selected?.verified" @click="approve(selected)" class="btn-approve">
                            <i class="bi bi-patch-check-fill"></i> Xác Minh
                        </button>
                        <span v-else class="approved-label"><i class="bi bi-patch-check-fill"></i> Đã xác minh</span>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.page-title{font-size:18px;font-weight:700;color:#0f172a;margin:0}.page-sub{font-size:12px;color:#94a3b8;margin:2px 0 0}
.stats-row{display:flex;gap:14px;margin-bottom:18px}
.scard{background:#fff;border-radius:14px;padding:16px;display:flex;align-items:center;gap:12px;border:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,.05);flex:1}
.scard i{font-size:26px;flex-shrink:0}.snum{font-size:24px;font-weight:800;color:#0f172a;margin:0;line-height:1}.slbl{font-size:11px;color:#94a3b8;margin:0}
.table-card{background:#fff;border-radius:14px;border:1px solid #f1f5f9;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.data-table{width:100%;border-collapse:collapse;font-size:13px}
.data-table th{text-align:left;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;padding:13px 16px;background:#f8fafc;border-bottom:1px solid #f1f5f9}
.data-table td{padding:12px 16px;border-bottom:1px solid #f8fafc;vertical-align:middle}
.trow:last-child td{border-bottom:none}.trow:hover td{background:#fafbff}
.idx{color:#cbd5e1;font-size:12px;font-weight:600}
.user-cell{display:flex;align-items:center;gap:10px}
.ava{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;font-weight:700;flex-shrink:0}
.fw{font-weight:600;color:#0f172a;margin:0}.sm{font-size:11px;color:#94a3b8;margin:0}
.room-badge{background:#eff6ff;color:#2563eb;font-size:11px;font-weight:600;padding:3px 9px;border-radius:99px}
.plan-paid{background:#f0fdf4;color:#16a34a;font-size:11px;font-weight:600;padding:3px 9px;border-radius:99px}
.plan-free{background:#f1f5f9;color:#64748b;font-size:11px;font-weight:600;padding:3px 9px;border-radius:99px}
.ver-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 9px;border-radius:99px}
.ver-ok{background:#f0fdf4;color:#16a34a}.ver-pending{background:#fff7ed;color:#ea580c}
.act-btn{padding:7px 12px;border-radius:9px;border:none;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px}
.act-primary{background:#7c3aed;color:#fff}.act-primary:hover{background:#6d28d9}
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.5);display:flex;align-items:center;justify-content:center;z-index:1000;backdrop-filter:blur(3px)}
.modal-box{background:#fff;border-radius:18px;width:440px;max-width:92vw;box-shadow:0 20px 60px rgba(0,0,0,.15);overflow:hidden}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #f1f5f9}
.modal-header h3{font-size:15px;font-weight:700;color:#0f172a;margin:0}
.modal-close{width:30px;height:30px;border-radius:8px;border:none;background:#f8fafc;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center}
.modal-body{padding:20px 22px;text-align:center}
.ll-avatar{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;font-size:26px;font-weight:800;display:flex;align-items:center;justify-content:center;margin:0 auto 10px}
.ll-name{font-size:16px;font-weight:700;color:#0f172a;margin:0}.ll-email{font-size:12px;color:#94a3b8;margin:4px 0 14px}
.info-block{background:#f8fafc;border-radius:12px;padding:12px 14px;text-align:left;margin-bottom:12px}
.ib-row{display:flex;gap:12px;font-size:13px;padding:4px 0}.ib-l{width:80px;color:#94a3b8;font-weight:500;flex-shrink:0}.ib-v{color:#0f172a;font-weight:500}
.cccd-preview{border:2px dashed #e2e8f0;border-radius:12px;padding:24px;color:#94a3b8;font-size:36px;display:flex;flex-direction:column;align-items:center;gap:6px}
.cccd-preview span{font-size:12px}
.modal-footer{display:flex;gap:8px;padding:14px 22px;border-top:1px solid #f1f5f9}
.btn-cancel{flex:1;padding:9px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer}
.btn-approve{flex:2;padding:9px;border-radius:10px;border:none;background:#7c3aed;color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px}
.btn-approve:hover{background:#6d28d9}
.approved-label{flex:2;display:flex;align-items:center;justify-content:center;gap:6px;font-size:13px;font-weight:700;color:#16a34a;background:#f0fdf4;border-radius:10px;padding:9px}
</style>
