export const vehicleTypeLabels = {
    care_vehicle : "福祉車両",
    company_car : "普通社用車",
    private_car : "私用車",
    none : "車不要",
}

export const getVehicleTypeLabel = (vehicleTypeChoice) => {
     return vehicleTypeLabels[vehicleTypeChoice] ?? vehicleTypeChoice;
};

export const vehicleTypeShortLabels = {
    care_vehicle : "福",
    company_car : "普",
    private_car : "私",
    none : "",
}

export const getVehicleTypeShortLabel = (vehicleTypeChoice) => {
     return vehicleTypeShortLabels[vehicleTypeChoice] ?? vehicleTypeChoice;
};
