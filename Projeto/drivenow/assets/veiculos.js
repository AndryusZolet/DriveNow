document.getElementById("veiculo_placa").addEventListener("input", function () {
    const placa = this.value.toUpperCase();
    const regexAntiga = /^[A-Z]{3}-[0-9]{4}$/;
    const regexMercosul = /^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/;

    if (regexAntiga.test(placa) || regexMercosul.test(placa)) {
        this.classList.remove("is-invalid");
        this.classList.add("is-valid");
    } else {
        this.classList.remove("is-valid");
        this.classList.add("is-invalid");
    }
});

const modelosPorMarca = {
    "Chevrolet": [
        "Onix", "Onix Plus", "Prisma", "Cruze", "Cruze Sport6", "S10", "Tracker",
        "Spin", "Montana", "Cobalt", "Captiva", "Astra", "Vectra", "Classic",
        "Camaro", "Equinox", "Trailblazer", "Silverado", "Bolt EV", "Blazer EV"
    ],
    "Fiat": [
        "Uno", "Palio", "Argo", "Mobi", "Strada", "Toro", "Cronos", "Siena",
        "Idea", "Punto", "Bravo", "Tempra", "Fastback", "Pulse", "Fiorino",
        "Ducato", "500e"
    ],
    "Ford": [
        "Ka", "Fiesta", "Focus", "Ranger", "EcoSport", "Fusion", "Edge",
        "Maverick", "F-150", "Territory", "Bronco", "Mustang"
    ],
    "Volkswagen": [
        "Gol", "Voyage", "Polo", "Virtus", "Fox", "T-Cross", "Nivus", "Taos",
        "Jetta", "Saveiro", "Amarok", "Tiguan Allspace", "Passat", "Golf"
    ],
    "Toyota": [
        "Corolla", "Yaris", "Etios", "Hilux", "SW4", "RAV4", "Camry",
        "Corolla Cross", "Prius", "Yaris Cross"
    ],
    "Hyundai": [
        "HB20", "HB20S", "Creta", "Tucson", "Santa Fe", "i30", "Azera",
        "Kona", "ix35", "HR", "IONIQ 5", "Palisade"
    ],
    "Honda": [
        "Civic", "City", "Fit", "HR-V", "WR-V", "Accord", "CR-V",
        "ZR-V", "Civic Type R", "City Hatchback", "City Sedan"
    ],
    "Renault": [
        "KwID", "Sandero", "Logan", "Duster", "Captur", "Oroch", "Fluence",
        "Kardian", "Master", "Zoe", "Kwid E-Tech", "Megane E-Tech", "Kangoo"
    ],
    "Nissan": [
        "March", "Versa", "Kicks", "Frontier", "Sentra", "Altima", "Leaf",
        "X-Trail"
    ],
    "Peugeot": [
        "208", "2008", "308", "3008", "408", "Partner", "Expert", "Boxer",
        "E-2008", "5008"
    ],
    "Citroën": [
        "C3", "C3 Aircross", "C4 Cactus", "C4 Lounge", "Aircross",
        "Xsara Picasso", "Berlingo", "Jumpy", "Jumper", "DS3", "DS4", "DS5"
    ],
    "Jeep": [
        "Renegade", "Compass", "Commander", "Cherokee", "Wrangler",
        "Gladiator", "Grand Cherokee 4xe"
    ],
    "Mitsubishi": [
        "L200 Triton", "Outlander", "ASX", "Pajero", "Eclipse Cross",
        "Pajero Sport"
    ],
    "Kia": [
        "Sportage", "Cerato", "Sorento", "Soul", "Picanto", "Bongo",
        "Stonic", "Seltos", "EV9", "EV5", "K4", "Niro", "Carnival"
    ],
    "Mercedes-Benz": [
        "A 180", "A 200", "A 250", "A 35 AMG", "A 45 AMG", "B 200", "C 180",
        "C 200", "C 250", "C 300", "C 43 AMG", "C 63 AMG", "CLA 180", "CLA 200",
        "CLA 250", "CLA 35 AMG", "CLA 45 AMG", "CLS 450", "CLS 53 AMG", "E 200",
        "E 300", "E 350", "E 400", "E 43 AMG", "E 63 AMG", "S 500", "S 580",
        "S 63 AMG", "GLA 200", "GLA 250", "GLA 35 AMG", "GLA 45 AMG", "GLB 200",
        "GLB 250", "GLB 35 AMG", "GLC 200", "GLC 250", "GLC 300", "GLC 43 AMG",
        "GLC 63 AMG", "GLE 350", "GLE 400", "GLE 450", "GLE 53 AMG", "GLE 63 AMG",
        "GLS 450", "GLS 580", "GLS 63 AMG", "EQB 250", "EQB 300", "EQB 350",
        "EQC 400", "EQS 450", "EQS 580", "EQS 53 AMG", "SL 400", "SL 500",
        "SL 63 AMG", "SLC 180", "SLC 200", "SLC 300", "SLC 43 AMG", "Sprinter 315",
        "Sprinter 415", "Sprinter 515", "Sprinter 516"
    ],
    "BMW": [
        "118i", "120i", "320i", "330i", "M3", "M4", "X1", "X2", "X3", "X4",
        "X5", "X6", "X7", "Z4", "i3", "i4", "iX", "iX3", "i7"
    ],
    "Audi": [
        "A3", "A4", "A5", "A6", "A7", "A8", "Q3", "Q5", "Q7", "Q8",
        "e-tron", "RS3", "RS4", "RS5", "RS6", "RS7", "TT", "R8"
    ],
    "BYD": [
        "Dolphin", "Seal", "Han", "Tang", "Song", "Yuan Plus", "e1",
        "e2", "e3", "e5", "T3", "T5", "T6", "T7"
    ],
    "Chery": [
        "Arrizo 5", "Arrizo 5 Plus", "Arrizo 5 GT", "Arrizo 6", "Arrizo 8",
        "Tiggo 2", "Tiggo 3x", "Tiggo 5x", "Tiggo 7", "Tiggo 8", "Omoda 5"
    ]
};

document.getElementById("veiculo_marca").addEventListener("change", function () {
    const marcaSelecionada = this.value;
    const modeloSelect = document.getElementById("veiculo_modelo");

    modeloSelect.innerHTML = "<option value=''>Selecione o modelo</option>";

    if (modelosPorMarca[marcaSelecionada]) {
        modelosPorMarca[marcaSelecionada].forEach(function (modelo) {
            const option = document.createElement("option");
            option.value = modelo;
            option.text = modelo;

            // Verifica se o modelo atual está selecionado
            if (modelo === modeloSelect.dataset.selected) {
                option.selected = true;
            }

            modeloSelect.appendChild(option);
        });
    }
});

// Executa a lógica ao carregar a página para preencher o modelo selecionado
window.addEventListener("DOMContentLoaded", function () {
    const marcaSelecionada = document.getElementById("veiculo_marca").value;
    const modeloSelect = document.getElementById("veiculo_modelo");

    if (modelosPorMarca[marcaSelecionada]) {
        modelosPorMarca[marcaSelecionada].forEach(function (modelo) {
            const option = document.createElement("option");
            option.value = modelo;
            option.text = modelo;

            // Verifica se o modelo atual está selecionado
            if (modelo === modeloSelect.dataset.selected) {
                option.selected = true;
            }

            modeloSelect.appendChild(option);
        });
    }
});