export const hasStaff = (r, staffId) => r.staff_assignments.some(a => a.staff_id === staffId);
