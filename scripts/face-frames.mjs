import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import jpeg from 'jpeg-js';
import { PNG } from 'pngjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const humanPath = path.join(root, 'node_modules', '@vladmandic', 'human', 'dist', 'human.node-wasm.js');
const modelPath = path.join(root, 'node_modules', '@vladmandic', 'human', 'models');
const wasmPath = path.join(root, 'node_modules', '@tensorflow', 'tfjs-backend-wasm', 'dist');
const nativeFetch = globalThis.fetch;

globalThis.fetch = async (input, init) => {
    const url = input instanceof URL ? input : new URL(input.url ?? input);
    if (url.protocol === 'file:') {
        return new Response(await fs.readFile(fileURLToPath(url)), { status: 200 });
    }

    return nativeFetch(input, init);
};

function decodeImage(buffer) {
    let decoded;
    if (buffer[0] === 0xff && buffer[1] === 0xd8) {
        decoded = jpeg.decode(buffer, { useTArray: true, formatAsRGBA: true });
    } else if (buffer.subarray(0, 8).equals(Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]))) {
        decoded = PNG.sync.read(buffer);
    } else {
        throw new Error('Use a JPEG or PNG camera image.');
    }

    if (!decoded.width || !decoded.height || decoded.width > 4096 || decoded.height > 4096) {
        throw new Error('The camera image dimensions are not supported.');
    }

    const rgb = new Uint8Array(decoded.width * decoded.height * 3);
    for (let source = 0, target = 0; source < decoded.data.length; source += 4) {
        rgb[target++] = decoded.data[source];
        rgb[target++] = decoded.data[source + 1];
        rgb[target++] = decoded.data[source + 2];
    }

    return { data: rgb, width: decoded.width, height: decoded.height };
}

async function main() {
    const imagePaths = process.argv.slice(2);
    if (imagePaths.length !== 4) {
        throw new Error('Exactly four camera frames are required.');
    }

    const humanModule = await import(pathToFileURL(humanPath).href);
    const modelsUrl = pathToFileURL(`${modelPath}${path.sep}`).href;
    const wasmUrl = pathToFileURL(`${wasmPath}${path.sep}`).href;
    const human = new humanModule.Human({
        backend: 'wasm',
        modelBasePath: modelsUrl,
        wasmPath: wasmUrl,
        debug: false,
        warmup: 'face',
        face: {
            enabled: true,
            detector: { enabled: true, rotation: true, maxDetected: 2, minConfidence: 0.2, skipFrames: 0, skipTime: 0 },
            mesh: { enabled: true },
            iris: { enabled: true },
            emotion: { enabled: false },
            description: { enabled: true, skipFrames: 0, skipTime: 0 },
            antispoof: { enabled: true, skipFrames: 0, skipTime: 0 },
            liveness: { enabled: true, skipFrames: 0, skipTime: 0 },
        },
        gesture: { enabled: true },
        body: { enabled: false },
        hand: { enabled: false },
        object: { enabled: false },
    });

    await human.tf.setBackend('wasm');
    await human.tf.ready();
    await human.load();

    const frames = [];
    for (const imagePath of imagePaths) {
        const image = decodeImage(await fs.readFile(imagePath));
        const tensor = human.tf.tensor3d(image.data, [image.height, image.width, 3], 'int32');
        const result = await human.detect(tensor);
        human.tf.dispose(tensor);

        if (result.face.length !== 1) {
            throw new Error('Each camera frame must contain exactly one clearly visible face.');
        }

        const face = result.face[0];
        if (!Array.isArray(face.embedding) || face.embedding.length < 64) {
            throw new Error('The face could not be analyzed. Improve the lighting and try again.');
        }

        frames.push({
            embedding: face.embedding,
            confidence: face.faceScore || face.boxScore || 0,
            real: face.real || 0,
            live: face.live || 0,
            gestures: result.gesture.map((gesture) => gesture.gesture),
        });
    }

    process.stdout.write(JSON.stringify({ frames }));
}

main().catch((error) => {
    process.stderr.write(`${error.message}\n`);
    process.exitCode = 1;
});
