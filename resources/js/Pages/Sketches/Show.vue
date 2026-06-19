<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({ sketch: Object });

const canvasRef = ref(null);
const activeTool = ref('pen');
const strokeColor = ref('#818CF8');
const strokeWidth = ref(3);
const isDrawing = ref(false);
const ctx = ref(null);
const lastX = ref(0);
const lastY = ref(0);
const saving = ref(false);

const tools = [
    { id: 'pen', label: 'Pen', icon: 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z' },
    { id: 'eraser', label: 'Eraser', icon: 'M5.25 7.5A2.25 2.25 0 017.5 5.25h9a2.25 2.25 0 012.25 2.25v9a2.25 2.25 0 01-2.25 2.25h-9a2.25 2.25 0 01-2.25-2.25v-9z' },
];

const colors = ['#818CF8', '#F59E0B', '#10B981', '#EF4444', '#EC4899', '#FFFFFF'];

onMounted(() => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    canvas.width = canvas.parentElement.clientWidth;
    canvas.height = canvas.parentElement.clientHeight;
    ctx.value = canvas.getContext('2d');
    ctx.value.lineCap = 'round';
    ctx.value.lineJoin = 'round';

    // Load existing canvas data
    if (props.sketch.canvas_data?.imageData) {
        const img = new Image();
        img.onload = () => ctx.value.drawImage(img, 0, 0);
        img.src = props.sketch.canvas_data.imageData;
    }

    window.addEventListener('resize', handleResize);
});

onUnmounted(() => window.removeEventListener('resize', handleResize));

const handleResize = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const imageData = canvas.toDataURL();
    canvas.width = canvas.parentElement.clientWidth;
    canvas.height = canvas.parentElement.clientHeight;
    const img = new Image();
    img.onload = () => ctx.value.drawImage(img, 0, 0);
    img.src = imageData;
};

const startDraw = (e) => {
    isDrawing.value = true;
    const rect = canvasRef.value.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    lastX.value = clientX - rect.left;
    lastY.value = clientY - rect.top;
};

const draw = (e) => {
    if (!isDrawing.value) return;
    e.preventDefault();
    const rect = canvasRef.value.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    const x = clientX - rect.left;
    const y = clientY - rect.top;

    ctx.value.beginPath();
    ctx.value.moveTo(lastX.value, lastY.value);
    ctx.value.lineTo(x, y);

    if (activeTool.value === 'eraser') {
        ctx.value.globalCompositeOperation = 'destination-out';
        ctx.value.lineWidth = strokeWidth.value * 5;
    } else {
        ctx.value.globalCompositeOperation = 'source-over';
        ctx.value.strokeStyle = strokeColor.value;
        ctx.value.lineWidth = strokeWidth.value;
    }

    ctx.value.stroke();
    lastX.value = x;
    lastY.value = y;
};

const stopDraw = () => { isDrawing.value = false; };

const clearCanvas = () => {
    if (!confirm('Clear the entire canvas?')) return;
    ctx.value.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
};

const saveCanvas = async () => {
    saving.value = true;
    const imageData = canvasRef.value.toDataURL('image/png');
    try {
        await fetch(route('sketches.saveCanvas', props.sketch.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({ canvas_data: { imageData } }),
        });
    } catch (err) {
        console.error('Save failed:', err);
    }
    saving.value = false;
};

const exportPNG = () => {
    const link = document.createElement('a');
    link.download = `${props.sketch.title || 'sketch'}.png`;
    link.href = canvasRef.value.toDataURL('image/png');
    link.click();
};
</script>

<template>
    <Head :title="sketch.title" />
    <div class="h-screen flex flex-col bg-nexboard-base">
        <!-- Toolbar -->
        <div class="h-14 flex items-center justify-between px-4 border-b border-white/10 bg-nexboard-surface/80 backdrop-blur-xl shrink-0">
            <div class="flex items-center gap-3">
                <Link :href="route('sketches.index')" class="p-1.5 rounded-nex text-nexboard-on-surface-variant hover:bg-white/5 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                </Link>
                <span class="text-sm font-semibold text-white">{{ sketch.title }}</span>
                <span class="text-xs text-nexboard-on-surface-variant">{{ sketch.project?.name }}</span>
            </div>

            <!-- Tools -->
            <div class="flex items-center gap-1 glass-card px-2 py-1">
                <button v-for="tool in tools" :key="tool.id" @click="activeTool = tool.id"
                    :class="activeTool === tool.id ? 'bg-indigo-500 text-white' : 'text-nexboard-on-surface-variant hover:bg-white/10'"
                    class="p-2 rounded-full transition-all" :title="tool.label">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" :d="tool.icon" /></svg>
                </button>
                <div class="w-px h-6 bg-white/10 mx-1" />
                <button v-for="c in colors" :key="c" @click="strokeColor = c; activeTool = 'pen'"
                    :class="strokeColor === c && activeTool === 'pen' ? 'ring-2 ring-white ring-offset-2 ring-offset-nexboard-surface' : ''"
                    :style="{ backgroundColor: c }" class="w-5 h-5 rounded-full transition-all" />
                <div class="w-px h-6 bg-white/10 mx-1" />
                <input v-model.number="strokeWidth" type="range" min="1" max="20" class="w-16 accent-indigo-500" />
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <button @click="clearCanvas" class="btn-secondary text-xs py-1.5 px-3">Clear</button>
                <button @click="exportPNG" class="btn-secondary text-xs py-1.5 px-3">Export PNG</button>
                <button @click="saveCanvas" :disabled="saving" class="btn-primary text-xs py-1.5 px-3">
                    {{ saving ? 'Saving...' : 'Save' }}
                </button>
            </div>
        </div>

        <!-- Canvas Area -->
        <div class="flex-1 relative overflow-hidden bg-nexboard-base cursor-crosshair">
            <canvas ref="canvasRef"
                @mousedown="startDraw" @mousemove="draw" @mouseup="stopDraw" @mouseleave="stopDraw"
                @touchstart="startDraw" @touchmove="draw" @touchend="stopDraw"
                class="absolute inset-0" />
        </div>
    </div>
</template>
