import { useCallback, useEffect, useState } from 'react';

export interface DevicePosition {
    latitude: number;
    longitude: number;
    accuracy: number;
}

export type PositionStatus = 'idle' | 'locating' | 'ready' | 'denied' | 'unavailable';

const OPTIONS: PositionOptions = { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 };

function read(): Promise<DevicePosition> {
    return new Promise((resolve, reject) => {
        if (!('geolocation' in navigator)) {
            reject(Object.assign(new Error('unsupported'), { code: -1 }));
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (p) => resolve({ latitude: p.coords.latitude, longitude: p.coords.longitude, accuracy: p.coords.accuracy }),
            reject,
            OPTIONS,
        );
    });
}

/**
 * Browser geolocation for the public form. Asks once on mount (when enabled) and exposes `refresh()`
 * so the position can be re-read right before submitting.
 */
export function useDevicePosition(enabled: boolean) {
    const [status, setStatus] = useState<PositionStatus>('idle');
    const [position, setPosition] = useState<DevicePosition | null>(null);

    const refresh = useCallback(async (): Promise<DevicePosition | null> => {
        setStatus('locating');
        try {
            const next = await read();
            setPosition(next);
            setStatus('ready');
            return next;
        } catch (error) {
            // code 1 = PERMISSION_DENIED; anything else (timeout, no signal, unsupported) is "unavailable".
            setStatus((error as GeolocationPositionError).code === 1 ? 'denied' : 'unavailable');
            return null;
        }
    }, []);

    useEffect(() => {
        if (enabled) refresh();
    }, [enabled, refresh]);

    return { status, position, refresh };
}
