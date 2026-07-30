import React, {
    useEffect, useRef, useState,
    useImperativeHandle, forwardRef,
} from 'react';
import { motion, useAnimation } from 'framer-motion';

/**
 * LoginBear – interactive SVG bear mascot (forwardRef version).
 *
 * Props:   isCovering {boolean}
 * Ref API: ref.current.wiggle() – trigger tickle wiggle from parent
 */
const LoginBear = forwardRef(function LoginBear({ isCovering = false }, ref) {
    const leftArmControls  = useAnimation();
    const rightArmControls = useAnimation();
    const bearControls     = useAnimation();
    const svgRef           = useRef(null);

    const [isBlinking,   setIsBlinking]   = useState(false);
    const [isTickled,    setIsTickled]    = useState(false);
    const [pupilOffset,  setPupilOffset]  = useState({ x: 0, y: 0 });

    const blinkTimerRef  = useRef(null);
    const tickleTimerRef = useRef(null);
    const isCoveringRef  = useRef(isCovering);
    useEffect(() => { isCoveringRef.current = isCovering; }, [isCovering]);

    /* ──────────────────────────────────────────
       Expose wiggle() to parent via ref
    ────────────────────────────────────────── */
    useImperativeHandle(ref, () => ({
        wiggle() {
            setIsTickled(true);
            bearControls.start({
                rotate: [-5, 5, -5, 5, -4, 4, -2, 2, 0],
                transition: { duration: 0.65, ease: 'easeInOut' },
            });
            clearTimeout(tickleTimerRef.current);
            tickleTimerRef.current = setTimeout(() => setIsTickled(false), 900);
        },
    }));

    /* ──────────────────────────────────────────
       Eye tracking — cursor → pupil offset
    ────────────────────────────────────────── */
    useEffect(() => {
        const onMove = (e) => {
            if (!svgRef.current || isCoveringRef.current) {
                setPupilOffset({ x: 0, y: 0 });
                return;
            }
            const rect   = svgRef.current.getBoundingClientRect();
            const scaleX = 160 / rect.width;
            const scaleY = 185 / rect.height;
            const mx     = (e.clientX - rect.left)  * scaleX;
            const my     = (e.clientY - rect.top)   * scaleY;

            // Both eyes share same mid-point for direction
            const dx   = mx - 80;
            const dy   = my - 82;
            const dist = Math.sqrt(dx * dx + dy * dy);
            const max  = 3.5; // SVG units of max pupil travel
            const k    = dist > 0 ? Math.min(max / dist, 1) : 0;
            setPupilOffset({ x: dx * k, y: dy * k });
        };

        window.addEventListener('mousemove', onMove);
        return () => window.removeEventListener('mousemove', onMove);
    }, []);

    /* ──────────────────────────────────────────
       Periodic blink
    ────────────────────────────────────────── */
    const scheduleBlink = () => {
        const delay = 2800 + Math.random() * 2200;
        blinkTimerRef.current = setTimeout(() => {
            setIsBlinking(true);
            setTimeout(() => { setIsBlinking(false); scheduleBlink(); }, 130);
        }, delay);
    };

    /* ──────────────────────────────────────────
       Idle — scratch belly
    ────────────────────────────────────────── */
    const startScratching = () => {
        leftArmControls.start({ x: 0, y: 0, rotate: 0,
            transition: { type: 'spring', stiffness: 200, damping: 22 } });
        rightArmControls.start({
            rotate: [0, 22, 5, 22, 5, 22, 0],
            x:      [0, -8, -5, -8, -5, -8, 0],
            y:      [0,  5,  4,  5,  4,  5, 0],
            transition: {
                duration: 0.85, ease: 'easeInOut',
                repeat: Infinity, repeatDelay: 2.8,
            },
        });
    };

    /* ──────────────────────────────────────────
       Cover / uncover eyes
    ────────────────────────────────────────── */
    const coverEyes = () => {
        rightArmControls.stop();
        leftArmControls.start({
            x: 24, y: -61, rotate: 35,
            transition: { type: 'spring', stiffness: 150, damping: 14 },
        });
        rightArmControls.start({
            x: -24, y: -61, rotate: -35,
            transition: { type: 'spring', stiffness: 150, damping: 14 },
        });
    };

    const uncoverEyes = async () => {
        rightArmControls.stop();
        await Promise.all([
            leftArmControls.start({ x: 0, y: 0, rotate: 0,
                transition: { type: 'spring', stiffness: 150, damping: 14 } }),
            rightArmControls.start({ x: 0, y: 0, rotate: 0,
                transition: { type: 'spring', stiffness: 150, damping: 14 } }),
        ]);
        startScratching();
    };

    /* ──────────────────────────────────────────
       Belly hover tickle
    ────────────────────────────────────────── */
    const handleBellyEnter = () => {
        setIsTickled(true);
        bearControls.start({
            rotate: [-5, 5, -5, 5, -4, 4, -2, 2, 0],
            transition: { duration: 0.65, ease: 'easeInOut' },
        });
        clearTimeout(tickleTimerRef.current);
        tickleTimerRef.current = setTimeout(() => setIsTickled(false), 900);
    };

    /* ──────────────────────────────────────────
       Lifecycle
    ────────────────────────────────────────── */
    useEffect(() => {
        if (isCovering) coverEyes();
        else uncoverEyes();
    }, [isCovering]);

    useEffect(() => {
        startScratching();
        scheduleBlink();
        return () => {
            leftArmControls.stop();
            rightArmControls.stop();
            clearTimeout(blinkTimerRef.current);
            clearTimeout(tickleTimerRef.current);
        };
    }, []);

    /* ──────────────────────────────────────────
       Render helpers
    ────────────────────────────────────────── */
    const px = pupilOffset.x;
    const py = pupilOffset.y;

    const renderEyes = () => {
        if (isCovering) return (
            <g>
                <path d="M57 82 Q66 76 75 82" stroke="#5a3e2b" strokeWidth="2.8" fill="none" strokeLinecap="round" />
                <path d="M85 82 Q94 76 103 82" stroke="#5a3e2b" strokeWidth="2.8" fill="none" strokeLinecap="round" />
            </g>
        );
        if (isBlinking || isTickled) return (
            <g>
                <path d="M57 84 Q66 79 75 84" stroke="#5a3e2b" strokeWidth="3" fill="none" strokeLinecap="round" />
                <path d="M85 84 Q94 79 103 84" stroke="#5a3e2b" strokeWidth="3" fill="none" strokeLinecap="round" />
            </g>
        );
        return (
            <g>
                {/* Whites */}
                <circle cx="66" cy="82" r="10" fill="white" />
                <circle cx="94" cy="82" r="10" fill="white" />
                {/* Pupils – follow cursor */}
                <circle cx={67 + px} cy={83 + py} r="5.5" fill="#1a1a1a" />
                <circle cx={95 + px} cy={83 + py} r="5.5" fill="#1a1a1a" />
                {/* Shine */}
                <circle cx={68.5 + px} cy={81 + py} r="2" fill="white" />
                <circle cx={96.5 + px} cy={81 + py} r="2" fill="white" />
            </g>
        );
    };

    const renderMouth = () => {
        if (isTickled) return (
            <g>
                <path d="M71 107 Q80 119 89 107" stroke="#3d2010" strokeWidth="2" fill="#c27f5c" strokeLinecap="round" />
                <ellipse cx="80" cy="112" rx="6" ry="4" fill="#8b3a2c" />
            </g>
        );
        return <path d="M73 108 Q80 115 87 108" stroke="#3d2010" strokeWidth="2" fill="none" strokeLinecap="round" />;
    };

    /* ──────────────────────────────────────────
       JSX
    ────────────────────────────────────────── */
    return (
        <div className="flex flex-col items-center select-none" aria-hidden="true">
            <motion.svg
                ref={svgRef}
                animate={bearControls}
                viewBox="0 0 160 185"
                width="160"
                height="185"
                xmlns="http://www.w3.org/2000/svg"
                style={{ overflow: 'visible', transformOrigin: '80px 180px' }}
            >
                {/* Shadow */}
                <ellipse cx="80" cy="180" rx="38" ry="5.5" fill="rgba(0,0,0,0.12)" />

                {/* Ears — behind head */}
                <circle cx="44"  cy="52" r="16" fill="#5a3e2b" />
                <circle cx="44"  cy="52" r="9"  fill="#c27f5c" />
                <circle cx="116" cy="52" r="16" fill="#5a3e2b" />
                <circle cx="116" cy="52" r="9"  fill="#c27f5c" />

                {/* Body */}
                <ellipse cx="80" cy="145" rx="38" ry="34" fill="#5a3e2b" />

                {/* Belly — hover = geliat */}
                <ellipse
                    cx="80" cy="149" rx="24" ry="22"
                    fill="#8b5e3c"
                    style={{ cursor: 'pointer' }}
                    onMouseEnter={handleBellyEnter}
                />

                {/* Legs */}
                <ellipse cx="63" cy="172" rx="14" ry="10" fill="#5a3e2b" />
                <ellipse cx="97" cy="172" rx="14" ry="10" fill="#5a3e2b" />

                {/* Head */}
                <circle cx="80" cy="88" r="46" fill="#7a5040" />

                {/* Face patch */}
                <ellipse cx="80" cy="98" rx="28" ry="24" fill="#c27f5c" />

                {/* Eyes */}
                {renderEyes()}

                {/* Nose */}
                <ellipse cx="80" cy="100" rx="8"   ry="5.5" fill="#3d2010" />
                <ellipse cx="78" cy="98.5" rx="2.5" ry="1.5" fill="#6b4225" />

                {/* Mouth */}
                {renderMouth()}

                {/* Blush */}
                <ellipse cx="59"  cy="104" rx="9" ry="5" fill="#e8947a" opacity="0.45" />
                <ellipse cx="101" cy="104" rx="9" ry="5" fill="#e8947a" opacity="0.45" />

                {/* Arms — AFTER head → always in front of face */}

                {/* Left Arm */}
                <motion.g animate={leftArmControls}>
                    <ellipse cx="46" cy="134" rx="12" ry="8"  fill="#5a3e2b" transform="rotate(-30 46 134)" />
                    <ellipse cx="38" cy="143" rx="10" ry="7.5" fill="#7a5040" />
                    <circle  cx="34" cy="141" r="1.5" fill="#5a3e2b" opacity="0.5" />
                    <circle  cx="38" cy="139" r="1.5" fill="#5a3e2b" opacity="0.5" />
                    <circle  cx="42" cy="141" r="1.5" fill="#5a3e2b" opacity="0.5" />
                </motion.g>

                {/* Right Arm */}
                <motion.g animate={rightArmControls}>
                    <ellipse cx="114" cy="134" rx="12" ry="8"  fill="#5a3e2b" transform="rotate(30 114 134)" />
                    <ellipse cx="122" cy="143" rx="10" ry="7.5" fill="#7a5040" />
                    <circle  cx="118" cy="141" r="1.5" fill="#5a3e2b" opacity="0.5" />
                    <circle  cx="122" cy="139" r="1.5" fill="#5a3e2b" opacity="0.5" />
                    <circle  cx="126" cy="141" r="1.5" fill="#5a3e2b" opacity="0.5" />
                </motion.g>
            </motion.svg>
        </div>
    );
});

export default LoginBear;
