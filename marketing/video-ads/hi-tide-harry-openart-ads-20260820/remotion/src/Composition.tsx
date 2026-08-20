import { Video } from "@remotion/media";
import {
  AbsoluteFill,
  Composition,
  Easing,
  Sequence,
  interpolate,
  staticFile,
  useCurrentFrame,
  useVideoConfig,
} from "remotion";

const FPS = 24;
const CLIP_FRAMES = 121;

type AdSpec = {
  asset: string;
  eyebrow: string;
  headline: string;
  support: string;
  cta: string;
};

const ads: Record<string, AdSpec> = {
  "Grand-Return": {
    asset: "assets/01-grand-return.mp4",
    eyebrow: "MBSH ’96 Reunion",
    headline: "The moment has arrived.",
    support: "Come back to the story.",
    cta: "Join the roll call",
  },
  "Roll-Call": {
    asset: "assets/02-roll-call-walk.mp4",
    eyebrow: "Class of 1996",
    headline: "The roll call is open.",
    support: "Your seat in the story is waiting.",
    cta: "Register today",
  },
  "Memory-Opens": {
    asset: "assets/03-memory-opens.mp4",
    eyebrow: "The Hi-Tide Lives On",
    headline: "The memories never left.",
    support: "Neither did the Hi-Tide.",
    cta: "Come home to ’96",
  },
};

const Clamp = {
  extrapolateLeft: "clamp" as const,
  extrapolateRight: "clamp" as const,
};

export const HiTideAd: React.FC<{ ad: AdSpec }> = ({ ad }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const easing = Easing.bezier(0.16, 1, 0.3, 1);
  const eyebrow = interpolate(frame, [2, 10], [0, 1], { ...Clamp, easing });
  const rule = interpolate(frame, [5, 15], [0, 1], { ...Clamp, easing });
  const headline = interpolate(frame, [8, 22], [1.17, 1], { ...Clamp, easing });
  const headlineOpacity = interpolate(frame, [8, 18], [0, 1], {
    ...Clamp,
    easing,
  });
  const support = interpolate(frame, [18, 31], [44, 0], { ...Clamp, easing });
  const cta = interpolate(frame, [36, 48], [58, 0], { ...Clamp, easing });
  const bloom = interpolate(frame, [34, 55], [0, 0.36], { ...Clamp, easing });
  const close = interpolate(frame, [fps * 4.72, CLIP_FRAMES - 1], [1, 0], Clamp);

  return (
    <AbsoluteFill style={{ backgroundColor: "#100507", overflow: "hidden" }}>
      <Video
        src={staticFile(ad.asset)}
        objectFit="cover"
        style={{ height: "100%", width: "100%" }}
      />
      <AbsoluteFill
        style={{
          background:
            "radial-gradient(ellipse 74% 54% at 10% 20%, rgba(16,5,7,.9) 0%, rgba(16,5,7,.58) 48%, rgba(16,5,7,0) 76%)",
        }}
      />
      <AbsoluteFill
        style={{
          opacity: close,
          padding: "214px 88px 150px",
          color: "#fff6ec",
          display: "flex",
          flexDirection: "column",
          justifyContent: "space-between",
        }}
      >
        <div style={{ width: 790 }}>
          <div
            style={{
              opacity: eyebrow,
              translate: `${interpolate(frame, [2, 10], [-65, 0], Clamp)}px 0`,
              color: "#d79a43",
              fontFamily: "ui-monospace, Menlo, monospace",
              fontSize: 23,
              fontWeight: 800,
              letterSpacing: ".17em",
              textTransform: "uppercase",
            }}
          >
            {ad.eyebrow}
          </div>
          <div
            style={{
              width: 196,
              height: 8,
              margin: "28px 0 31px",
              backgroundColor: "#d79a43",
              transformOrigin: "left center",
              scale: `${rule} 1`,
            }}
          />
          <div
            style={{
              opacity: headlineOpacity,
              scale: headline,
              maxWidth: 790,
              fontFamily: '"Arial Black", sans-serif',
              fontSize: 116,
              fontWeight: 900,
              lineHeight: 0.84,
              letterSpacing: "-.066em",
              textTransform: "uppercase",
            }}
          >
            {ad.headline}
          </div>
          <div
            style={{
              opacity: interpolate(frame, [18, 30], [0, 1], {
                ...Clamp,
                easing,
              }),
              translate: `0 ${support}px`,
              maxWidth: 680,
              marginTop: 31,
              fontFamily: "Georgia, serif",
              fontSize: 46,
              fontStyle: "italic",
              lineHeight: 1.02,
              letterSpacing: "-.035em",
            }}
          >
            {ad.support}
          </div>
        </div>
        <div
          style={{
            display: "flex",
            alignItems: "flex-end",
            justifyContent: "space-between",
            gap: 32,
            translate: `0 ${cta}px`,
            opacity: interpolate(frame, [36, 48], [0, 1], {
              ...Clamp,
              easing,
            }),
          }}
        >
          <div
            style={{
              maxWidth: 500,
              color: "#100507",
              backgroundColor: "#fff6ec",
              padding: "28px 29px 25px",
              boxShadow: "14px 14px 0 #e23426",
              fontFamily: '"Arial Black", sans-serif',
              fontSize: 38,
              fontWeight: 900,
              lineHeight: 0.88,
              letterSpacing: "-.04em",
              textTransform: "uppercase",
            }}
          >
            {ad.cta}
          </div>
          <div
            style={{
              color: "#d8d7d6",
              paddingBottom: 4,
              fontFamily: "ui-monospace, Menlo, monospace",
              fontSize: 17,
              fontWeight: 800,
              lineHeight: 1.45,
              letterSpacing: ".1em",
              textAlign: "right",
              textTransform: "uppercase",
            }}
          >
            <span style={{ display: "block" }}>Nov 07 2026</span>
            <span style={{ display: "block" }}>MBSH96REUNION.COM</span>
          </div>
        </div>
      </AbsoluteFill>
      <div
        style={{
          position: "absolute",
          right: -180,
          bottom: -180,
          width: 640,
          height: 640,
          borderRadius: "50%",
          opacity: bloom,
          background:
            "radial-gradient(circle, rgba(226,52,38,.47) 0%, rgba(226,52,38,.18) 37%, rgba(226,52,38,0) 70%)",
        }}
      />
    </AbsoluteFill>
  );
};

const FilmBurn: React.FC<{
  frame: number;
  from: number;
  direction: "left" | "right";
}> = ({ frame, from, direction }) => {
  const opacity = interpolate(frame, [from, from + 5, from + 16], [0, 0.9, 0], Clamp);
  const x = interpolate(
    frame,
    [from, from + 16],
    direction === "right" ? [420, -480] : [-420, 480],
    Clamp,
  );
  const origin = direction === "right" ? "112% 52%" : "-12% 54%";
  return (
    <div
      style={{
        position: "absolute",
        inset: -280,
        zIndex: 20,
        opacity,
        translate: `${x}px 0`,
        pointerEvents: "none",
        background: `radial-gradient(circle at ${origin}, rgba(215,154,67,.98) 0%, rgba(226,52,38,.66) 20%, rgba(16,5,7,0) 55%)`,
      }}
    />
  );
};

export const MasterTrailer: React.FC = () => {
  const frame = useCurrentFrame();
  return (
    <AbsoluteFill style={{ backgroundColor: "#100507" }}>
      <Sequence durationInFrames={CLIP_FRAMES}>
        <HiTideAd ad={ads["Grand-Return"]} />
      </Sequence>
      <Sequence from={CLIP_FRAMES} durationInFrames={CLIP_FRAMES}>
        <HiTideAd ad={ads["Roll-Call"]} />
      </Sequence>
      <Sequence from={CLIP_FRAMES * 2} durationInFrames={CLIP_FRAMES}>
        <HiTideAd ad={ads["Memory-Opens"]} />
      </Sequence>
      <FilmBurn frame={frame} from={CLIP_FRAMES - 8} direction="right" />
      <FilmBurn frame={frame} from={CLIP_FRAMES * 2 - 8} direction="left" />
    </AbsoluteFill>
  );
};

export const HiTideAds: React.FC = () => (
  <>
    <Composition
      id="Grand-Return"
      component={() => <HiTideAd ad={ads["Grand-Return"]} />}
      durationInFrames={CLIP_FRAMES}
      fps={FPS}
      width={1080}
      height={1920}
    />
    <Composition
      id="Roll-Call"
      component={() => <HiTideAd ad={ads["Roll-Call"]} />}
      durationInFrames={CLIP_FRAMES}
      fps={FPS}
      width={1080}
      height={1920}
    />
    <Composition
      id="Memory-Opens"
      component={() => <HiTideAd ad={ads["Memory-Opens"]} />}
      durationInFrames={CLIP_FRAMES}
      fps={FPS}
      width={1080}
      height={1920}
    />
    <Composition
      id="Master-Trailer"
      component={MasterTrailer}
      durationInFrames={CLIP_FRAMES * 3}
      fps={FPS}
      width={1080}
      height={1920}
    />
  </>
);
