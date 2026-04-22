import * as faceapi from 'face-api.js';

const FACE_MODEL_BASE_URL = '/vendor/face-api/models';
const FACE_MATCH_MAX_DISTANCE = 0.55;

let faceModelsLoadPromise = null;
let activeMediaStream = null;

const attendanceForm = document.querySelector('[data-student-attendance-form]');
const registrationForm = document.querySelector('[data-face-registration-form]');

if (attendanceForm !== null || registrationForm !== null) {
    const cameraPreviewElement = document.getElementById('face_camera_preview');

    const attendanceUi = {
        prepareButton: document.getElementById('prepare_backup_attendance_button'),
        submitButton: document.getElementById('submit_backup_attendance_button'),
        latitudeInput: document.getElementById('attendance_latitude'),
        longitudeInput: document.getElementById('attendance_longitude'),
        descriptorInput: document.getElementById('attendance_face_descriptor'),
        locationStatus: document.getElementById('location_status_message'),
        locationDistance: document.getElementById('location_distance_message'),
        faceStatus: document.getElementById('face_status_message'),
        faceDistance: document.getElementById('face_distance_message'),
    };

    const registrationUi = {
        captureButton: document.getElementById('capture_registration_face_button'),
        descriptorInput: document.getElementById('registration_face_descriptor'),
        statusMessage: document.getElementById('registration_status_message'),
    };

    if (attendanceForm !== null && attendanceUi.prepareButton !== null) {
        attendanceUi.prepareButton.addEventListener('click', async () => {
            await verifyAttendanceForm(attendanceForm, attendanceUi, cameraPreviewElement);
        });
    }

    if (
        registrationForm !== null
        && registrationUi.captureButton !== null
        && registrationUi.descriptorInput !== null
    ) {
        registrationUi.captureButton.addEventListener('click', async () => {
            registrationUi.captureButton.disabled = true;
            updateText(registrationUi.statusMessage, 'Memproses pendaftaran template wajah...', 'neutral');

            try {
                const descriptor = await captureFaceDescriptor(cameraPreviewElement);

                registrationUi.descriptorInput.value = JSON.stringify(descriptor);
                updateText(registrationUi.statusMessage, 'Template wajah berhasil diambil. Menyimpan data...', 'success');

                registrationForm.requestSubmit();
            } catch (error) {
                updateText(registrationUi.statusMessage, normalizeErrorMessage(error), 'error');
                registrationUi.captureButton.disabled = false;
            }
        });
    }

    window.addEventListener('beforeunload', stopCameraStream);
}

async function verifyAttendanceForm(form, ui, cameraPreviewElement) {
    if (
        ui.submitButton === null
        || ui.latitudeInput === null
        || ui.longitudeInput === null
        || ui.descriptorInput === null
    ) {
        return;
    }

    ui.prepareButton.disabled = true;
    ui.submitButton.disabled = true;

    updateText(ui.locationStatus, 'Mengambil lokasi perangkat...', 'neutral');
    updateText(ui.locationDistance, '', 'neutral');
    updateText(ui.faceStatus, 'Menunggu proses verifikasi wajah...', 'neutral');
    updateText(ui.faceDistance, '', 'neutral');

    try {
        const schoolLatitude = parseCoordinate(form.dataset.schoolLatitude);
        const schoolLongitude = parseCoordinate(form.dataset.schoolLongitude);

        if (schoolLatitude === null || schoolLongitude === null) {
            throw new Error('Koordinat sekolah belum diatur. Hubungi admin.');
        }

        const currentLocation = await getCurrentLocation();
        const distanceMeter = calculateDistanceInMeters(
            currentLocation.latitude,
            currentLocation.longitude,
            schoolLatitude,
            schoolLongitude,
        );

        const maxRadiusMeter = Number(form.dataset.maxRadius ?? 0);

        ui.latitudeInput.value = currentLocation.latitude.toFixed(7);
        ui.longitudeInput.value = currentLocation.longitude.toFixed(7);

        if (Number.isFinite(maxRadiusMeter) && distanceMeter > maxRadiusMeter) {
            updateText(ui.locationStatus, 'Lokasi di luar radius absensi.', 'error');
            updateText(ui.locationDistance, `Jarak terdeteksi ${distanceMeter.toFixed(2)} meter.`, 'error');
            throw new Error('Anda berada di luar radius absensi yang diizinkan.');
        }

        updateText(ui.locationStatus, 'Lokasi valid dan berada dalam radius.', 'success');
        updateText(ui.locationDistance, `Jarak terdeteksi ${distanceMeter.toFixed(2)} meter.`, 'success');

        const enrolledDescriptor = parseDescriptorDataset(form.dataset.enrolledFaceDescriptor);

        if (enrolledDescriptor === null) {
            throw new Error('Template wajah belum tersedia. Silakan daftarkan wajah terlebih dahulu.');
        }

        updateText(ui.faceStatus, 'Memproses verifikasi wajah...', 'neutral');

        const capturedDescriptor = await captureFaceDescriptor(cameraPreviewElement);
        const faceDistance = calculateEuclideanDistance(capturedDescriptor, enrolledDescriptor);

        if (faceDistance > FACE_MATCH_MAX_DISTANCE) {
            updateText(ui.faceStatus, 'Wajah tidak cocok dengan template terdaftar.', 'error');
            updateText(ui.faceDistance, `Jarak descriptor ${faceDistance.toFixed(4)}.`, 'error');
            throw new Error('Verifikasi wajah gagal. Pastikan wajah menghadap kamera dengan jelas.');
        }

        ui.descriptorInput.value = JSON.stringify(capturedDescriptor);

        updateText(ui.faceStatus, 'Wajah berhasil diverifikasi.', 'success');
        updateText(ui.faceDistance, `Jarak descriptor ${faceDistance.toFixed(4)}.`, 'success');

        ui.submitButton.disabled = false;
    } catch (error) {
        if (ui.submitButton !== null) {
            ui.submitButton.disabled = true;
        }

        if (ui.faceStatus !== null && (ui.faceStatus.textContent ?? '').trim() === 'Menunggu proses verifikasi wajah...') {
            updateText(ui.faceStatus, normalizeErrorMessage(error), 'error');
        }
    } finally {
        ui.prepareButton.disabled = false;
        stopCameraStream();
    }
}

/**
 * @returns {Promise<{latitude: number, longitude: number}>}
 */
function getCurrentLocation() {
    if (!('geolocation' in navigator)) {
        return Promise.reject(new Error('Browser ini tidak mendukung geolocation.'));
    }

    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                });
            },
            (error) => {
                let message = 'Gagal mengambil lokasi perangkat.';

                if (error.code === error.PERMISSION_DENIED) {
                    message = 'Izin lokasi ditolak. Mohon izinkan akses lokasi pada browser.';
                }

                if (error.code === error.TIMEOUT) {
                    message = 'Pengambilan lokasi timeout. Silakan coba lagi.';
                }

                reject(new Error(message));
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0,
            },
        );
    });
}

function calculateDistanceInMeters(latitudeA, longitudeA, latitudeB, longitudeB) {
    const earthRadiusMeter = 6371000;

    const latitudeADegree = toRadian(latitudeA);
    const latitudeBDegree = toRadian(latitudeB);
    const latitudeDelta = toRadian(latitudeB - latitudeA);
    const longitudeDelta = toRadian(longitudeB - longitudeA);

    const haversine = (Math.sin(latitudeDelta / 2) ** 2)
        + (Math.cos(latitudeADegree) * Math.cos(latitudeBDegree) * (Math.sin(longitudeDelta / 2) ** 2));

    const centralAngle = 2 * Math.atan2(Math.sqrt(haversine), Math.sqrt(1 - haversine));

    return earthRadiusMeter * centralAngle;
}

function calculateEuclideanDistance(left, right) {
    let sum = 0;

    for (let index = 0; index < left.length; index += 1) {
        const difference = left[index] - right[index];
        sum += difference ** 2;
    }

    return Math.sqrt(sum);
}

function toRadian(value) {
    return value * (Math.PI / 180);
}

async function captureFaceDescriptor(cameraPreviewElement) {
    await ensureFaceApiReady();

    await startCameraStream(cameraPreviewElement);

    const detectionOptions = new faceapi.TinyFaceDetectorOptions({
        inputSize: 320,
        scoreThreshold: 0.5,
    });

    for (let attempt = 0; attempt < 12; attempt += 1) {
        const result = await faceapi
            .detectSingleFace(cameraPreviewElement, detectionOptions)
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (result !== undefined) {
            return Array.from(result.descriptor);
        }

        await delay(250);
    }

    throw new Error('Wajah tidak terdeteksi. Pastikan pencahayaan cukup dan wajah menghadap kamera.');
}

async function ensureFaceApiReady() {
    await ensureFaceModelsReady();
}

async function ensureFaceModelsReady() {
    if (faceModelsLoadPromise !== null) {
        await faceModelsLoadPromise;

        return;
    }

    faceModelsLoadPromise = Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(FACE_MODEL_BASE_URL),
        faceapi.nets.faceLandmark68Net.loadFromUri(FACE_MODEL_BASE_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(FACE_MODEL_BASE_URL),
    ]);

    await faceModelsLoadPromise;
}

async function startCameraStream(cameraPreviewElement) {
    if (cameraPreviewElement === null) {
        throw new Error('Elemen video kamera tidak ditemukan.');
    }

    if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
        throw new Error('Browser ini tidak mendukung akses kamera.');
    }

    stopCameraStream();

    activeMediaStream = await navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: 'user',
            width: { ideal: 640 },
            height: { ideal: 480 },
        },
        audio: false,
    });

    cameraPreviewElement.srcObject = activeMediaStream;

    await cameraPreviewElement.play();
}

function stopCameraStream() {
    if (activeMediaStream !== null) {
        activeMediaStream.getTracks().forEach((track) => track.stop());
        activeMediaStream = null;
    }
}

function parseCoordinate(value) {
    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return null;
    }

    return numeric;
}

function parseDescriptorDataset(value) {
    if (value === undefined || value === null || value === '' || value === 'null') {
        return null;
    }

    try {
        const parsed = JSON.parse(value);

        if (!Array.isArray(parsed) || parsed.length !== 128) {
            return null;
        }

        const normalized = parsed.map((item) => Number(item));

        if (normalized.some((item) => !Number.isFinite(item))) {
            return null;
        }

        return normalized;
    } catch (error) {
        return null;
    }
}

function normalizeErrorMessage(error) {
    if (error instanceof Error) {
        return error.message;
    }

    return 'Proses verifikasi gagal. Silakan coba lagi.';
}

function updateText(element, message, state) {
    if (element === null) {
        return;
    }

    element.textContent = message;
    element.classList.remove('text-slate-600', 'text-slate-700', 'text-slate-500', 'text-emerald-600', 'text-rose-600');

    if (state === 'success') {
        element.classList.add('text-emerald-600');

        return;
    }

    if (state === 'neutral') {
        element.classList.add('text-slate-600');

        return;
    }

    element.classList.add('text-rose-600');
}

function delay(milliseconds) {
    return new Promise((resolve) => {
        window.setTimeout(resolve, milliseconds);
    });
}
