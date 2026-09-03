//phần trạng thái phòng
export const roomStatusMap = {
    available: { label: 'Còn phòng', class: 'room-badge-available' },
    rented: { label: 'Đã thuê', class: 'room-badge-rented' },
    maintenance: { label: 'Bảo trì', class: 'room-badge-maintenance' },
    deposited: { label: 'Đã cọc', class: 'room-badge-deposited' },
    expiring_soon: { label: 'Sắp hết hạn', class: 'room-badge-renewal' },
    pending_renewal: { label: 'Chờ gia hạn', class: 'room-badge-renewal' },
    suspended: { label: 'Tạm ngưng', class: 'room-badge-suspended' },
    under_construction: { label: 'Đang xây', class: 'room-badge-construction' },
    pending: { label: 'Chờ duyệt', class: 'room-badge-pending' },
    approved: { label: 'Đang hiển thị', class: 'room-badge-approved' },
    draft: { label: 'Bản nháp', class: 'room-badge-draft' },
    hidden: { label: 'Đã ẩn', class: 'room-badge-hidden' },
};
export const getStatusLabel = (status) => {
    return roomStatusMap[status]?.label || status;
};
export const getStatusClass = (status) => {
    return roomStatusMap[status]?.class || 'room-badge-available';
};