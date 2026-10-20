export const vehicleTypeLabels = {
    care_vehicle : "福祉車両",
    company_car : "普通社用車",
    private_car : "私用車",
    none : "車不要",
    }

export const getVehicleTypeLabel = (vehicleTypeChoice) => {
     return vehicleTypeLabels[vehicleTypeChoice] ?? vehicleTypeChoice;
};
