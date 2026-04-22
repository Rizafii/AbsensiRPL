const FACE_API_CDN_SCRIPT_URL = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js';
const FACE_MODEL_BASE_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
const FACE_MATCH_MAX_DISTANCE = 0.55;
const FACE_MODAL_NAME = 'attendance-face-recognition-modal';

let faceApiLoadPromise = null;
let faceModelsLoadPromise = null;
let activeMediaStream = null;
let activeFlow = null;

const attendanceForm = document.querySelector('[data-student-attendance-form]');
const registrationForm = document.querySelector('[data-face-registration-form]');
const cameraPreviewElement = document.getElementById('face_camera_preview_modal');
const modalHintElement = document.getElementById('modal_face_hint');
const modalStatusMessageElement = document.getElementById('modal_face_status_message');
const confirmFaceButton = document.getElementById('confirm_face_recognition_button');
const closeFaceButton = document.getElementById('close_face_recognition_button');

const attendanceUi = {
    startButton: document.getElementById('start_backup_attendance_button'),
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

if (attendanceForm !== null || registrationForm !== null) {
    if (attendanceForm !== null && attendanceUi.startButton !== null) {
        attendanceUi.startButton.addEventListener('click', async () => {
            await prepareAttendanceAndOpenFaceModal();
        });
    }

    if (
        registrationForm !== null
        && registrationUi.captureButton !== null
        && registrationUi.descriptorInput !== null
    ) {
        registrationUi.captureButton.addEventListener('click', async () => {
            await openRegistrationFaceModal();
        });
    }

    if (confirmFaceButton !== null) {
        confirmFaceButton.addEventListener('click', async () => {
            await confirmFaceRecognition();
        });
    }

    if (closeFaceButton !== null) {
        closeFaceButton.addEventListener('click', () => {
            closeFaceRecognitionModal();
        });
    }

    window.addEventListener('beforeunload', stopCameraStream);
}

async function prepareAttendanceAndOpenFaceModal() {
    if (
        attendanceForm === null
        || attendanceUi.startButton === null
        || attendanceUi.latitudeInput === null
        || attendanceUi.longitudeInput === null
        || attendanceUi.descriptorInput === null
    ) {
        return;
    }

    attendanceUi.startButton.disabled = true;
    attendanceUi.descriptorInput.value = '';

    updateText(attendanceUi.locationStatus, 'Mengambil lokasi perangkat...', 'neutral');
    updateText(attendanceUi.locationDistance, '', 'neutral');
    updateText(attendanceUi.faceStatus, 'Menunggu verifikasi wajah di modal.', 'neutral');
    updateText(attendanceUi.faceDistance, '', 'neutral');

    try {
        const schoolLatitude = parseCoordinate(attendanceForm.dataset.schoolLatitude);
        const schoolLongitude = parseCoordinate(attendanceForm.dataset.schoolLongitude);

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

        const maxRadiusMeter = Number(attendanceForm.dataset.maxRadius ?? 0);

        attendanceUi.latitudeInput.value = currentLocation.latitude.toFixed(7);
        attendanceUi.longitudeInput.value = currentLocation.longitude.toFixed(7);

        if (Number.isFinite(maxRadiusMeter) && distanceMeter > maxRadiusMeter) {
            updateText(attendanceUi.locationStatus, 'Lokasi di luar radius absensi.', 'error');
            updateText(attendanceUi.locationDistance, `Jarak terdeteksi ${distanceMeter.toFixed(2)} meter.`, 'error');
            throw new Error('Anda berada di luar radius absensi yang diizinkan.');
        }

        updateText(attendanceUi.locationStatus, 'Lokasi valid dan berada dalam radius.', 'success');
        updateText(attendanceUi.locationDistance, `Jarak terdeteksi ${distanceMeter.toFixed(2)} meter.`, 'success');

        await openFaceRecognitionModal('attendance');
    } catch (error) {
        updateText(attendanceUi.faceStatus, normalizeErrorMessage(error), 'error');
    } finally {
        attendanceUi.startButton.disabled = false;
    }
}

async function openRegistrationFaceModal() {
    if (registrationUi.captureButton !== null) {
        registrationUi.captureButton.disabled = true;
    }

    updateText(registrationUi.statusMessage, 'Menyiapkan kamera untuk pendaftaran template wajah...', 'neutral');

    try {
        await openFaceRecognitionModal('registration');
    } catch (error) {
        updateText(registrationUi.statusMessage, normalizeErrorMessage(error), 'error');

        if (registrationUi.captureButton !== null) {
            registrationUi.captureButton.disabled = false;
        }
    }
}

async function openFaceRecognitionModal(flow) {
    if (cameraPreviewElement === null || confirmFaceButton === null) {
        throw new Error('Komponen modal face recognition tidak ditemukan.');
    }

    activeFlow = flow;

    if (modalHintElement !== null) {
        modalHintElement.textContent = flow === 'attendance'
            ? 'Koordinat sudah valid. Posisikan wajah di dalam frame lalu klik Verifikasi Wajah.'
            : 'Posisikan wajah di dalam frame lalu klik Verifikasi Wajah untuk mendaftarkan template.';
    }

    updateText(modalStatusMessageElement, 'Menyalakan kamera...', 'neutral');

    if (typeof showModal === 'function') {
        showModal(FACE_MODAL_NAME);
    }

    confirmFaceButton.disabled = true;

    try {
        await startCameraStream(cameraPreviewElement);

        updateText(modalStatusMessageElement, 'Memuat model face recognition.', 'neutral');
        await ensureFaceApiReady();

        updateText(modalStatusMessageElement, 'Kamera siap. Pastikan wajah berada di dalam frame.', 'success');
        confirmFaceButton.disabled = false;
    } catch (error) {
        closeFaceRecognitionModal();

        throw error;
    }
}

async function confirmFaceRecognition() {
    if (
        cameraPreviewElement === null
        || confirmFaceButton === null
        || activeFlow === null
    ) {
        return;
    }

    confirmFaceButton.disabled = true;
    updateText(modalStatusMessageElement, 'Mendeteksi wajah...', 'neutral');

    try {
        const capturedDescriptor = await captureFaceDescriptor(cameraPreviewElement);

        if (activeFlow === 'registration') {
            if (registrationForm === null || registrationUi.descriptorInput === null) {
                throw new Error('Form pendaftaran wajah tidak ditemukan.');
            }

            registrationUi.descriptorInput.value = JSON.stringify(capturedDescriptor);
            updateText(registrationUi.statusMessage, 'Template wajah berhasil diambil. Menyimpan data...', 'success');
            closeFaceRecognitionModal();
            registrationForm.requestSubmit();

            return;
        }

        if (attendanceForm === null || attendanceUi.descriptorInput === null) {
            throw new Error('Form absensi tidak ditemukan.');
        }

        const enrolledDescriptor = parseDescriptorDataset(attendanceForm.dataset.enrolledFaceDescriptor);

        if (enrolledDescriptor === null) {
            throw new Error('Template wajah belum tersedia. Silakan daftarkan wajah terlebih dahulu.');
        }

        const faceDistance = calculateEuclideanDistance(capturedDescriptor, enrolledDescriptor);

        if (faceDistance > FACE_MATCH_MAX_DISTANCE) {
            updateText(attendanceUi.faceStatus, 'Wajah tidak cocok dengan template terdaftar.', 'error');
            updateText(attendanceUi.faceDistance, `Jarak descriptor ${faceDistance.toFixed(4)}.`, 'error');
            throw new Error('Verifikasi wajah gagal. Pastikan wajah menghadap kamera dengan jelas.');
        }

        attendanceUi.descriptorInput.value = JSON.stringify(capturedDescriptor);
        updateText(attendanceUi.faceStatus, 'Wajah berhasil diverifikasi.', 'success');
        updateText(attendanceUi.faceDistance, `Jarak descriptor ${faceDistance.toFixed(4)}.`, 'success');

        closeFaceRecognitionModal();
        attendanceForm.requestSubmit();
    } catch (error) {
        updateText(modalStatusMessageElement, normalizeErrorMessage(error), 'error');

        if (activeFlow === 'attendance') {
            updateText(attendanceUi.faceStatus, normalizeErrorMessage(error), 'error');
        }

        if (activeFlow === 'registration') {
            updateText(registrationUi.statusMessage, normalizeErrorMessage(error), 'error');
        }
    } finally {
        if (confirmFaceButton !== null && activeFlow !== null) {
            confirmFaceButton.disabled = false;
        }
    }
}

function closeFaceRecognitionModal() {
    if (typeof hideModal === 'function') {
        hideModal(FACE_MODAL_NAME);
    }

    stopCameraStream();

    if (confirmFaceButton !== null) {
        confirmFaceButton.disabled = true;
    }

    activeFlow = null;

    if (registrationUi.captureButton !== null) {
        registrationUi.captureButton.disabled = false;
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
    const faceApi = await ensureFaceApiReady();

    if (activeMediaStream === null) {
        await startCameraStream(cameraPreviewElement);
    }

    const detectionOptions = new faceApi.TinyFaceDetectorOptions({
        inputSize: 320,
        scoreThreshold: 0.5,
    });

    for (let attempt = 0; attempt < 16; attempt += 1) {
        const result = await faceApi
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
    if (window.faceapi !== undefined) {
        await ensureFaceModelsReady(window.faceapi);

        return window.faceapi;
    }

    if (faceApiLoadPromise === null) {
        faceApiLoadPromise = new Promise((resolve, reject) => {
            const scriptElement = document.createElement('script');
            scriptElement.src = FACE_API_CDN_SCRIPT_URL;
            scriptElement.async = true;

            scriptElement.onload = async () => {
                try {
                    await ensureFaceModelsReady(window.faceapi);
                    resolve(window.faceapi);
                } catch (error) {
                    faceApiLoadPromise = null;
                    reject(error);
                }
            };

            scriptElement.onerror = () => {
                faceApiLoadPromise = null;
                reject(new Error('Gagal memuat library face recognition.'));
            };

            document.head.appendChild(scriptElement);
        });
    }

    return faceApiLoadPromise;
}

async function ensureFaceModelsReady(faceApi) {
    if (faceModelsLoadPromise !== null) {
        await faceModelsLoadPromise;

        return;
    }

    faceModelsLoadPromise = Promise.all([
        faceApi.nets.tinyFaceDetector.loadFromUri(FACE_MODEL_BASE_URL),
        faceApi.nets.faceLandmark68Net.loadFromUri(FACE_MODEL_BASE_URL),
        faceApi.nets.faceRecognitionNet.loadFromUri(FACE_MODEL_BASE_URL),
    ]);

    try {
        await faceModelsLoadPromise;
    } catch (error) {
        faceModelsLoadPromise = null;

        throw new Error('Model face recognition gagal dimuat. Silakan cek koneksi internet Anda.');
    }
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
    element.classList.remove('text-slate-600', 'text-slate-700', 'text-slate-500', 'text-emerald-600', 'text-rose-600', 'text-amber-600');

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
