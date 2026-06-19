<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import rough from 'roughjs';

const props = defineProps({ sketch: Object });

// Core canvas and context refs
const canvasRef = ref(null);
const textInputRef = ref(null);
const ctx = ref(null);

// Excalidraw-like states
const elements = ref([]);
const undoStack = ref([]);
const redoStack = ref([]);
const pan = ref({ x: 0, y: 0 });
const zoom = ref(1);
const activeTool = ref('draw'); // select, hand, draw, line, arrow, rectangle, ellipse, diamond, text, eraser
const showGrid = ref(true);
const saving = ref(false);
const lastSaved = ref(null);

// Styling options for hand-drawn shapes
const strokeColor = ref('#818CF8');
const fillColor = ref('none'); // hex color or 'none'
const fillStyle = ref('hachure'); // hachure, solid, zigzag, cross-hatch, dots
const strokeWidth = ref(2); // 1, 2, 5
const strokeStyle = ref('solid'); // solid, dashed, dotted
const roughness = ref(1.2); // 0 (Architect), 1.2 (Artist), 2.5 (Cartoonist)

const tools = [
    { id: 'select', label: 'Selection', icon: 'M15.033 15.033a3 3 0 0 1-4.243 0l-4.243-4.243a3 3 0 0 1 0-4.243l4.243-4.243a3 3 0 0 1 4.243 0l4.243 4.243a3 3 0 0 1 0 4.243l-4.243 4.243Z' },
    { id: 'hand', label: 'Hand (Pan)', icon: 'M10.05 3.475a.75.75 0 0 1 .107 1.053l-3.25 4a.75.75 0 0 1-1.077.078l-1.5-1.5a.75.75 0 0 1 1.06-1.06l.91.91 2.804-3.45a.75.75 0 0 1 1.046-.031Zm5.05 4a.75.75 0 0 1 .107 1.053l-3.25 4a.75.75 0 0 1-1.077.078l-1.5-1.5a.75.75 0 1 1 1.06-1.06l.91.91 2.804-3.45a.75.75 0 0 1 1.046-.031Z' },
    { id: 'draw', label: 'Draw (Freehand)', icon: 'M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1-1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 3.42-3.42M9 3.5a5.5 5.5 0 0 0-5.5 5.5v.75c0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75V9A5.5 5.5 0 0 0 9 3.5z' },
    { id: 'line', label: 'Line', icon: 'M3.75 12h16.5' },
    { id: 'arrow', label: 'Arrow', icon: 'M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75' },
    { id: 'rectangle', label: 'Rectangle', icon: 'M5.25 7.5A2.25 2.25 0 0 1 7.5 5.25h9a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25v-9z' },
    { id: 'ellipse', label: 'Ellipse', icon: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z' },
    { id: 'diamond', label: 'Diamond', icon: 'M12 2.25l9 9.75-9 9.75-9-9.75 9-9.75z' },
    { id: 'text', label: 'Text', icon: 'M9 15h6M12 3v12M9 3h6' },
    { id: 'eraser', label: 'Eraser', icon: 'M19 7h-8L4 14.5V19h4.5L19 11.5V7z' },
];

const colors = ['#818CF8', '#F59E0B', '#10B981', '#EF4444', '#EC4899', '#38BDF8', '#FFFFFF'];
const fillColors = ['none', '#818CF833', '#F59E0B33', '#10B98133', '#EF444433', '#EC489933', '#38BDF833', '#FFFFFF22'];

// Track keyboard modifiers
const isSpacePressed = ref(false);
const isDrawing = ref(false);
const selectedElement = ref(null);
const previewElement = ref(null);
const dragStart = ref({ x: 0, y: 0 });
const elementOffset = ref({ x: 0, y: 0 });

// Text input state
const textInput = ref({
    show: false,
    x: 0,
    y: 0,
    text: '',
    canvasX: 0,
    canvasY: 0
});

// Setup scene drawing
const drawScene = () => {
    if (!canvasRef.value || !ctx.value) return;
    const canvas = canvasRef.value;
    
    // Clear whole screen
    ctx.value.clearRect(0, 0, canvas.width, canvas.height);
    
    ctx.value.save();
    // Apply pan and zoom transforms
    ctx.value.translate(pan.value.x, pan.value.y);
    ctx.value.scale(zoom.value, zoom.value);
    
    // Draw dots grid
    if (showGrid.value) {
        drawGridPattern();
    }
    
    const rc = rough.canvas(canvas);
    
    // Draw all stored elements
    elements.value.forEach(el => {
        drawElement(rc, ctx.value, el);
        // If element is selected, draw a bounding box outline
        if (activeTool.value === 'select' && selectedElement.value?.id === el.id) {
            drawSelectionOutline(ctx.value, el);
        }
    });
    
    // Draw current active preview drawing
    if (previewElement.value) {
        drawElement(rc, ctx.value, previewElement.value);
    }
    
    ctx.value.restore();
};

const drawGridPattern = () => {
    const canvas = canvasRef.value;
    const size = 30; // Grid spacing
    
    // Calculate visible boundaries based on pan and zoom
    const startX = -pan.value.x / zoom.value;
    const startY = -pan.value.y / zoom.value;
    const endX = (canvas.width - pan.value.x) / zoom.value;
    const endY = (canvas.height - pan.value.y) / zoom.value;
    
    ctx.value.fillStyle = 'rgba(255, 255, 255, 0.05)';
    const offsetGridX = startX - (startX % size);
    const offsetGridY = startY - (startY % size);
    
    for (let x = offsetGridX; x < endX; x += size) {
        for (let y = offsetGridY; y < endY; y += size) {
            ctx.value.beginPath();
            ctx.value.arc(x, y, 1, 0, 2 * Math.PI);
            ctx.value.fill();
        }
    }
};

const drawElement = (rc, context, el) => {
    const options = {
        stroke: el.stroke,
        strokeWidth: el.strokeWidth,
        roughness: el.roughness,
        fill: el.fill === 'none' ? undefined : el.fill,
        fillStyle: el.fillStyle,
        strokeLineDash: el.strokeStyle === 'dashed' ? [8, 8] : el.strokeStyle === 'dotted' ? [2, 6] : undefined,
    };
    
    if (el.type === 'rectangle') {
        rc.rectangle(el.x, el.y, el.width, el.height, options);
    } else if (el.type === 'ellipse') {
        rc.ellipse(el.x + el.width/2, el.y + el.height/2, el.width, el.height, options);
    } else if (el.type === 'diamond') {
        const top = [el.x + el.width / 2, el.y];
        const right = [el.x + el.width, el.y + el.height / 2];
        const bottom = [el.x + el.width / 2, el.y + el.height];
        const left = [el.x, el.y + el.height / 2];
        rc.polygon([top, right, bottom, left], options);
    } else if (el.type === 'line') {
        rc.line(el.x, el.y, el.x + el.width, el.y + el.height, options);
    } else if (el.type === 'arrow') {
        rc.line(el.x, el.y, el.x + el.width, el.y + el.height, options);
        // Arrow head
        const angle = Math.atan2(el.height, el.width);
        const headLength = 14;
        const arrowX = el.x + el.width;
        const arrowY = el.y + el.height;
        const p1 = [
            arrowX - headLength * Math.cos(angle - Math.PI / 6),
            arrowY - headLength * Math.sin(angle - Math.PI / 6)
        ];
        const p2 = [
            arrowX - headLength * Math.cos(angle + Math.PI / 6),
            arrowY - headLength * Math.sin(angle + Math.PI / 6)
        ];
        rc.polygon([[arrowX, arrowY], p1, p2], { ...options, fill: el.stroke, fillStyle: 'solid' });
    } else if (el.type === 'draw') {
        if (el.points.length > 1) {
            rc.linearPath(el.points, options);
        }
    } else if (el.type === 'text') {
        context.font = `${el.strokeWidth * 6 + 14}px "Architects Daughter", "Inter", sans-serif`;
        context.fillStyle = el.stroke;
        context.textBaseline = 'top';
        context.fillText(el.text, el.x, el.y);
    }
};

const drawSelectionOutline = (context, el) => {
    context.strokeStyle = '#818cf8';
    context.lineWidth = 1;
    context.setLineDash([4, 4]);
    
    if (el.type === 'draw') {
        // Draw overall bounding box for freehand scribbles
        let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
        el.points.forEach(([px, py]) => {
            if (px < minX) minX = px;
            if (px > maxX) maxX = px;
            if (py < minY) minY = py;
            if (py > maxY) maxY = py;
        });
        context.strokeRect(minX - 5, minY - 5, (maxX - minX) + 10, (maxY - minY) + 10);
    } else {
        const x = Math.min(el.x, el.x + el.width);
        const y = Math.min(el.y, el.y + el.height);
        const w = Math.abs(el.width);
        const h = Math.abs(el.height);
        context.strokeRect(x - 5, y - 5, w + 10, h + 10);
    }
    context.setLineDash([]);
};

// Hit testing
const getElementAtPosition = (x, y) => {
    for (let i = elements.value.length - 1; i >= 0; i--) {
        const el = elements.value[i];
        if (el.type === 'rectangle' || el.type === 'ellipse' || el.type === 'diamond') {
            const minX = Math.min(el.x, el.x + el.width);
            const maxX = Math.max(el.x, el.x + el.width);
            const minY = Math.min(el.y, el.y + el.height);
            const maxY = Math.max(el.y, el.y + el.height);
            if (x >= minX - 5 && x <= maxX + 5 && y >= minY - 5 && y <= maxY + 5) return el;
        } else if (el.type === 'line' || el.type === 'arrow') {
            const dist = distanceToSegment([x, y], [el.x, el.y], [el.x + el.width, el.y + el.height]);
            if (dist < 8) return el;
        } else if (el.type === 'draw') {
            for (const pt of el.points) {
                const dist = Math.hypot(pt[0] - x, pt[1] - y);
                if (dist < 12) return el;
            }
        } else if (el.type === 'text') {
            const minX = el.x;
            const maxX = el.x + (el.text.length * 10);
            const minY = el.y;
            const maxY = el.y + 24;
            if (x >= minX - 5 && x <= maxX + 5 && y >= minY - 5 && y <= maxY + 5) return el;
        }
    }
    return null;
};

const distanceToSegment = (p, v, w) => {
    const l2 = Math.pow(v[0] - w[0], 2) + Math.pow(v[1] - w[1], 2);
    if (l2 === 0) return Math.hypot(p[0] - v[0], p[1] - v[1]);
    let t = ((p[0] - v[0]) * (w[0] - v[0]) + (p[1] - v[1]) * (w[1] - v[1])) / l2;
    t = Math.max(0, Math.min(1, t));
    return Math.hypot(p[0] - (v[0] + t * (w[0] - v[0])), p[1] - (v[1] + t * (w[1] - v[1])));
};

// Canvas events
const handleMouseDown = (e) => {
    if (textInput.value.show) {
        finishTextInput();
        return;
    }
    
    const rect = canvasRef.value.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    
    // Map screen position to canvas coordinate space
    const canvasX = (clientX - rect.left - pan.value.x) / zoom.value;
    const canvasY = (clientY - rect.top - pan.value.y) / zoom.value;
    
    dragStart.value = { x: clientX, y: clientY };
    isDrawing.value = true;
    
    if (activeTool.value === 'hand' || isSpacePressed.value) {
        canvasRef.value.style.cursor = 'grabbing';
        return;
    }
    
    if (activeTool.value === 'select') {
        const clickedEl = getElementAtPosition(canvasX, canvasY);
        if (clickedEl) {
            selectedElement.value = clickedEl;
            elementOffset.value = {
                x: canvasX - clickedEl.x,
                y: canvasY - clickedEl.y,
                // store original points offset for freehand scribble
                points: clickedEl.points ? clickedEl.points.map(([px, py]) => [canvasX - px, canvasY - py]) : []
            };
            drawScene();
        } else {
            selectedElement.value = null;
            drawScene();
        }
        return;
    }
    
    if (activeTool.value === 'eraser') {
        const clickedEl = getElementAtPosition(canvasX, canvasY);
        if (clickedEl) {
            saveHistory();
            elements.value = elements.value.filter(el => el.id !== clickedEl.id);
            selectedElement.value = null;
            drawScene();
            autoSaveDebounced();
        }
        return;
    }
    
    if (activeTool.value === 'text') {
        textInput.value = {
            show: true,
            x: clientX,
            y: clientY,
            text: '',
            canvasX: canvasX,
            canvasY: canvasY
        };
        nextTick(() => {
            if (textInputRef.value) textInputRef.value.focus();
        });
        return;
    }
    
    // Shape tools
    saveHistory();
    const newId = 'el-' + Date.now();
    const baseElement = {
        id: newId,
        type: activeTool.value,
        x: canvasX,
        y: canvasY,
        width: 0,
        height: 0,
        stroke: strokeColor.value,
        fill: fillColor.value,
        fillStyle: fillStyle.value,
        strokeWidth: strokeWidth.value,
        strokeStyle: strokeStyle.value,
        roughness: roughness.value
    };
    
    if (activeTool.value === 'draw') {
        baseElement.points = [[canvasX, canvasY]];
    }
    
    previewElement.value = baseElement;
    drawScene();
};

const handleMouseMove = (e) => {
    if (!isDrawing.value) return;
    
    const rect = canvasRef.value.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    
    // Map screen position to canvas coordinate space
    const canvasX = (clientX - rect.left - pan.value.x) / zoom.value;
    const canvasY = (clientY - rect.top - pan.value.y) / zoom.value;
    
    if (activeTool.value === 'hand' || isSpacePressed.value) {
        pan.value.x += clientX - dragStart.value.x;
        pan.value.y += clientY - dragStart.value.y;
        dragStart.value = { x: clientX, y: clientY };
        drawScene();
        return;
    }
    
    if (activeTool.value === 'select' && selectedElement.value) {
        const el = selectedElement.value;
        if (el.type === 'draw') {
            el.points = el.points.map((pt, idx) => {
                const offset = elementOffset.value.points[idx];
                return [canvasX - offset[0], canvasY - offset[1]];
            });
            // Update center ref
            el.x = canvasX - elementOffset.value.x;
            el.y = canvasY - elementOffset.value.y;
        } else {
            el.x = canvasX - elementOffset.value.x;
            el.y = canvasY - elementOffset.value.y;
        }
        drawScene();
        return;
    }
    
    if (previewElement.value) {
        const el = previewElement.value;
        if (el.type === 'draw') {
            el.points.push([canvasX, canvasY]);
        } else {
            el.width = canvasX - el.x;
            el.height = canvasY - el.y;
        }
        drawScene();
    }
};

const handleMouseUp = () => {
    isDrawing.value = false;
    canvasRef.value.style.cursor = activeTool.value === 'hand' || isSpacePressed.value ? 'grab' : 'crosshair';
    
    if (previewElement.value) {
        elements.value.push(previewElement.value);
        previewElement.value = null;
        redoStack.value = []; // Clear redo stack on new action
        drawScene();
        autoSaveDebounced();
    } else if (activeTool.value === 'select' && selectedElement.value) {
        autoSaveDebounced();
    }
};

// Text completion
const finishTextInput = () => {
    if (!textInput.value.show) return;
    if (textInput.value.text.trim()) {
        saveHistory();
        elements.value.push({
            id: 'el-' + Date.now(),
            type: 'text',
            x: textInput.value.canvasX,
            y: textInput.value.canvasY,
            width: textInput.value.text.length * 10,
            height: 24,
            text: textInput.value.text,
            stroke: strokeColor.value,
            strokeWidth: strokeWidth.value,
            roughness: roughness.value
        });
        redoStack.value = [];
        drawScene();
        autoSaveDebounced();
    }
    textInput.value.show = false;
};

const cancelTextInput = () => {
    textInput.value.show = false;
};

// Panning and Zooming
const handleWheel = (e) => {
    e.preventDefault();
    const zoomFactor = 1.08;
    const rect = canvasRef.value.getBoundingClientRect();
    const mouseX = e.clientX - rect.left;
    const mouseY = e.clientY - rect.top;
    
    const canvasMouseX = (mouseX - pan.value.x) / zoom.value;
    const canvasMouseY = (mouseY - pan.value.y) / zoom.value;
    
    let newZoom = zoom.value;
    if (e.deltaY < 0) {
        newZoom = Math.min(newZoom * zoomFactor, 4);
    } else {
        newZoom = Math.max(newZoom / zoomFactor, 0.25);
    }
    
    pan.value = {
        x: mouseX - canvasMouseX * newZoom,
        y: mouseY - canvasMouseY * newZoom
    };
    zoom.value = newZoom;
    drawScene();
};

// Undo / Redo
const saveHistory = () => {
    undoStack.value.push(JSON.stringify(elements.value));
    if (undoStack.value.length > 50) undoStack.value.shift();
};

const handleUndo = () => {
    if (undoStack.value.length === 0) return;
    redoStack.value.push(JSON.stringify(elements.value));
    elements.value = JSON.parse(undoStack.value.pop());
    selectedElement.value = null;
    drawScene();
    autoSaveDebounced();
};

const handleRedo = () => {
    if (redoStack.value.length === 0) return;
    undoStack.value.push(JSON.stringify(elements.value));
    elements.value = JSON.parse(redoStack.value.pop());
    selectedElement.value = null;
    drawScene();
    autoSaveDebounced();
};

const clearCanvas = () => {
    if (!confirm('Clear the entire board?')) return;
    saveHistory();
    elements.value = [];
    selectedElement.value = null;
    drawScene();
    autoSaveDebounced();
};

// Keyboard listener for Space (pan tool override) & Delete key
const handleKeyDown = (e) => {
    if (e.code === 'Space' && !textInput.value.show) {
        e.preventDefault();
        isSpacePressed.value = true;
        canvasRef.value.style.cursor = 'grab';
    }
    if ((e.code === 'Delete' || e.code === 'Backspace') && activeTool.value === 'select' && selectedElement.value && !textInput.value.show) {
        saveHistory();
        elements.value = elements.value.filter(el => el.id !== selectedElement.value.id);
        selectedElement.value = null;
        drawScene();
        autoSaveDebounced();
    }
    // Ctrl+Z & Ctrl+Y shortcuts
    if (e.ctrlKey && e.code === 'KeyZ') {
        e.preventDefault();
        handleUndo();
    }
    if (e.ctrlKey && e.code === 'KeyY') {
        e.preventDefault();
        handleRedo();
    }
};

const handleKeyUp = (e) => {
    if (e.code === 'Space') {
        isSpacePressed.value = false;
        canvasRef.value.style.cursor = activeTool.value === 'hand' ? 'grab' : 'crosshair';
    }
};

// Auto-save debouncing
let saveTimer = null;
const autoSaveDebounced = () => {
    if (saveTimer) clearTimeout(saveTimer);
    saveTimer = setTimeout(() => {
        saveCanvas();
    }, 2500); // Save 2.5s after editing stops
};

const saveCanvas = async () => {
    saving.value = true;
    try {
        await fetch(route('sketches.saveCanvas', props.sketch.id), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({
                canvas_data: {
                    elements: elements.value,
                    pan: pan.value,
                    zoom: zoom.value
                }
            }),
        });
        lastSaved.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    } catch (err) {
        console.error('Auto-save failed:', err);
    }
    saving.value = false;
};

// Export PNG by drawing elements onto a fresh canvas size to fit content
const exportPNG = () => {
    const canvas = canvasRef.value;
    const link = document.createElement('a');
    link.download = `${props.sketch.title || 'sketch'}.png`;
    
    // Draw all onto a temp canvas with background color filled
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = canvas.width;
    tempCanvas.height = canvas.height;
    const tempCtx = tempCanvas.getContext('2d');
    
    // Fill dark background
    tempCtx.fillStyle = '#0b1326';
    tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
    
    // Apply pan & zoom
    tempCtx.translate(pan.value.x, pan.value.y);
    tempCtx.scale(zoom.value, zoom.value);
    
    const rc = rough.canvas(tempCanvas);
    elements.value.forEach(el => {
        drawElement(rc, tempCtx, el);
    });
    
    link.href = tempCanvas.toDataURL('image/png');
    link.click();
};

const handleResize = () => {
    if (!canvasRef.value) return;
    canvasRef.value.width = canvasRef.value.parentElement.clientWidth;
    canvasRef.value.height = canvasRef.value.parentElement.clientHeight;
    drawScene();
};

onMounted(() => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    
    // Resize canvas to fill the page container
    canvas.width = canvas.parentElement.clientWidth;
    canvas.height = canvas.parentElement.clientHeight;
    ctx.value = canvas.getContext('2d');
    
    // Load existing elements if present
    if (props.sketch.canvas_data?.elements) {
        elements.value = props.sketch.canvas_data.elements;
        if (props.sketch.canvas_data.pan) pan.value = props.sketch.canvas_data.pan;
        if (props.sketch.canvas_data.zoom) zoom.value = props.sketch.canvas_data.zoom;
    }
    
    drawScene();
    
    // Bind listeners
    window.addEventListener('resize', handleResize);
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('keyup', handleKeyUp);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('keyup', handleKeyUp);
    if (saveTimer) clearTimeout(saveTimer);
});

// Redraw scene when tool or settings change
watch([activeTool, strokeColor, fillColor, fillStyle, strokeWidth, strokeStyle, roughness, showGrid], () => {
    drawScene();
});
</script>

<template>
    <Head :title="sketch.title" />
    <div class="h-screen flex flex-col bg-nexboard-base overflow-hidden select-none font-sans">
        
        <!-- TOP TOOLBAR -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-white/10 bg-nexboard-surface/80 backdrop-blur-xl shrink-0 z-20">
            <div class="flex items-center gap-3">
                <Link :href="route('sketches.index')" class="p-2 rounded-xl text-nexboard-on-surface-variant hover:text-white hover:bg-white/5 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                <div>
                    <span class="text-sm font-semibold text-white tracking-wide block leading-none">{{ sketch.title }}</span>
                    <span class="text-[11px] text-nexboard-on-surface-variant mt-1 block">{{ sketch.project?.name || 'No Project' }}</span>
                </div>
            </div>

            <!-- Hand-drawn Toolbar (Excalidraw Center Toolbar Style) -->
            <div class="flex items-center gap-1.5 bg-nexboard-surface-container-low/90 border border-white/10 rounded-2xl px-3 py-1.5 shadow-xl">
                <button
                    v-for="tool in tools"
                    :key="tool.id"
                    @click="activeTool = tool.id"
                    :class="activeTool === tool.id ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-500/30' : 'text-nexboard-on-surface-variant hover:bg-white/5 hover:text-white'"
                    class="p-2 rounded-xl transition-all duration-200"
                    :title="tool.label"
                >
                    <!-- Customize Select icon -->
                    <svg v-if="tool.id === 'select'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <!-- Customize Hand icon -->
                    <svg v-else-if="tool.id === 'hand'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.05 3.475a.75.75 0 0 1 .107 1.053l-3.25 4a.75.75 0 0 1-1.077.078l-1.5-1.5a.75.75 0 0 1 1.06-1.06l.91.91 2.804-3.45a.75.75 0 0 1 1.046-.031Z" />
                    </svg>
                    <!-- Customize Text icon -->
                    <svg v-else-if="tool.id === 'text'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v-7.5m-3-3h6m-3 0v7.5" />
                    </svg>
                    <!-- SVG icons default path -->
                    <svg v-else-if="tool.id === 'line'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5" />
                    </svg>
                    <svg v-else-if="tool.id === 'arrow'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                    <svg v-else-if="tool.id === 'rectangle'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <svg v-else-if="tool.id === 'ellipse'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <svg v-else-if="tool.id === 'diamond'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M3 12h18" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="tool.icon" />
                    </svg>
                </button>
            </div>

            <!-- Right Controls: Save, Export, Status indicators -->
            <div class="flex items-center gap-3">
                <span class="text-xs text-nexboard-on-surface-variant">
                    <span v-if="saving" class="flex items-center gap-1.5 text-indigo-400">
                        <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Saving...
                    </span>
                    <span v-else-if="lastSaved" class="text-emerald-400">Saved at {{ lastSaved }}</span>
                    <span v-else>All changes local</span>
                </span>
                <button @click="handleUndo" class="p-2 rounded-xl text-nexboard-on-surface-variant hover:text-white hover:bg-white/5" title="Undo (Ctrl+Z)">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                </button>
                <button @click="handleRedo" class="p-2 rounded-xl text-nexboard-on-surface-variant hover:text-white hover:bg-white/5" title="Redo (Ctrl+Y)">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 000 12h3" /></svg>
                </button>
                <button @click="exportPNG" class="btn-secondary text-xs py-1.5 px-3">Export PNG</button>
                <button @click="saveCanvas" :disabled="saving" class="btn-primary text-xs py-1.5 px-3">
                    Manual Save
                </button>
            </div>
        </div>

        <!-- MAIN LAYOUT AREA -->
        <div class="flex-1 flex relative overflow-hidden">
            
            <!-- LEFT FLOATING OPTIONS PANEL (Excalidraw Sidebar Look) -->
            <div class="absolute left-6 top-6 bottom-6 w-64 bg-nexboard-surface-container-low/90 border border-white/10 rounded-2xl p-4 shadow-2xl flex flex-col gap-4 overflow-y-auto z-10 backdrop-blur-md">
                
                <!-- STROKE COLOR -->
                <div>
                    <label class="text-[10px] font-bold uppercase tracking-wider text-nexboard-on-surface-variant block mb-2">Stroke Color</label>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="c in colors"
                            :key="c"
                            @click="strokeColor = c"
                            :class="strokeColor === c ? 'ring-2 ring-indigo-500 ring-offset-2 ring-offset-nexboard-surface-container-low' : 'hover:scale-105'"
                            :style="{ backgroundColor: c }"
                            class="w-6 h-6 rounded-lg transition-all border border-white/15"
                        />
                    </div>
                </div>

                <!-- FILL COLOR -->
                <div>
                    <label class="text-[10px] font-bold uppercase tracking-wider text-nexboard-on-surface-variant block mb-2">Background Fill</label>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="fc in fillColors"
                            :key="fc"
                            @click="fillColor = fc"
                            :class="fillColor === fc ? 'ring-2 ring-indigo-500 ring-offset-2 ring-offset-nexboard-surface-container-low' : 'hover:scale-105'"
                            :style="{ backgroundColor: fc === 'none' ? 'transparent' : fc }"
                            class="w-6 h-6 rounded-lg transition-all border border-white/15 relative overflow-hidden"
                            title="Fill style color"
                        >
                            <span v-if="fc === 'none'" class="absolute inset-0 flex items-center justify-center text-red-500 text-xs font-bold leading-none select-none">/</span>
                        </button>
                    </div>
                </div>

                <!-- FILL STYLE -->
                <div v-if="fillColor !== 'none'">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-nexboard-on-surface-variant block mb-2">Fill Style</label>
                    <div class="grid grid-cols-3 gap-1 bg-white/5 p-1 rounded-xl">
                        <button
                            v-for="style in ['hachure', 'solid', 'cross-hatch']"
                            :key="style"
                            @click="fillStyle = style"
                            :class="fillStyle === style ? 'bg-indigo-500 text-white font-medium shadow-md' : 'text-nexboard-on-surface-variant hover:text-white'"
                            class="text-[10px] py-1 rounded-lg capitalize transition-all"
                        >
                            {{ style === 'hachure' ? 'Sketchy' : style === 'cross-hatch' ? 'Hatch' : 'Solid' }}
                        </button>
                    </div>
                </div>

                <!-- STROKE WIDTH -->
                <div>
                    <label class="text-[10px] font-bold uppercase tracking-wider text-nexboard-on-surface-variant block mb-2">Stroke Width</label>
                    <div class="grid grid-cols-3 gap-1 bg-white/5 p-1 rounded-xl text-center">
                        <button
                            v-for="w in [1, 2, 5]"
                            :key="w"
                            @click="strokeWidth = w"
                            :class="strokeWidth === w ? 'bg-indigo-500 text-white font-medium shadow-md' : 'text-nexboard-on-surface-variant hover:text-white'"
                            class="text-[10px] py-1 rounded-lg transition-all"
                        >
                            {{ w === 1 ? 'Thin' : w === 2 ? 'Medium' : 'Bold' }}
                        </button>
                    </div>
                </div>

                <!-- STROKE STYLE -->
                <div>
                    <label class="text-[10px] font-bold uppercase tracking-wider text-nexboard-on-surface-variant block mb-2">Stroke Style</label>
                    <div class="grid grid-cols-3 gap-1 bg-white/5 p-1 rounded-xl text-center">
                        <button
                            v-for="style in ['solid', 'dashed', 'dotted']"
                            :key="style"
                            @click="strokeStyle = style"
                            :class="strokeStyle === style ? 'bg-indigo-500 text-white font-medium shadow-md' : 'text-nexboard-on-surface-variant hover:text-white'"
                            class="text-[10px] py-1 rounded-lg capitalize transition-all"
                        >
                            {{ style }}
                        </button>
                    </div>
                </div>

                <!-- SLOPIUNESS / ROUGHNESS (EXCALIDRAW STYLE) -->
                <div>
                    <label class="text-[10px] font-bold uppercase tracking-wider text-nexboard-on-surface-variant block mb-2">Sloppiness (Hand-drawn)</label>
                    <div class="grid grid-cols-3 gap-1 bg-white/5 p-1 rounded-xl text-center">
                        <button
                            v-for="r in [0, 1.2, 2.5]"
                            :key="r"
                            @click="roughness = r"
                            :class="roughness === r ? 'bg-indigo-500 text-white font-medium shadow-md' : 'text-nexboard-on-surface-variant hover:text-white'"
                            class="text-[10px] py-1 rounded-lg transition-all"
                        >
                            {{ r === 0 ? 'Architect' : r === 1.2 ? 'Artist' : 'Cartoon' }}
                        </button>
                    </div>
                </div>

                <div class="h-px bg-white/10 my-2" />

                <!-- GRID TOGGLE & CLEAR -->
                <div class="flex flex-col gap-2">
                    <button
                        @click="showGrid = !showGrid"
                        :class="showGrid ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/30' : 'text-nexboard-on-surface-variant border border-white/10'"
                        class="w-full py-2 rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                        Grid Pattern
                    </button>
                    <button @click="clearCanvas" class="w-full border border-red-500/20 text-red-400 hover:bg-red-500/10 py-2 rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Clear Canvas
                    </button>
                </div>
            </div>

            <!-- CANVAS DRAWING AREA -->
            <div class="flex-1 relative overflow-hidden bg-nexboard-base cursor-crosshair h-full w-full">
                
                <!-- Active Interactive Canvas -->
                <canvas
                    ref="canvasRef"
                    @mousedown="handleMouseDown"
                    @mousemove="handleMouseMove"
                    @mouseup="handleMouseUp"
                    @mouseleave="handleMouseUp"
                    @touchstart="handleMouseDown"
                    @touchmove="handleMouseMove"
                    @touchend="handleMouseUp"
                    @wheel="handleWheel"
                    class="absolute inset-0 w-full h-full block"
                />

                <!-- Dynamic Floating Input for Text Tool -->
                <input
                    v-if="textInput.show"
                    ref="textInputRef"
                    v-model="textInput.text"
                    @blur="finishTextInput"
                    @keyup.enter="finishTextInput"
                    @keyup.esc="cancelTextInput"
                    :style="{
                        position: 'absolute',
                        left: `${textInput.x}px`,
                        top: `${textInput.y}px`,
                        font: `${strokeWidth * 6 + 14}px 'Architects Daughter', sans-serif`,
                        color: strokeColor,
                        background: 'transparent',
                        border: 'none',
                        outline: 'none',
                        caretColor: strokeColor,
                        zIndex: 50
                    }"
                    class="p-1 border-none focus:outline-none focus:ring-0"
                    placeholder="Type text..."
                />

                <!-- Navigation Guide overlay -->
                <div class="absolute right-6 bottom-6 flex flex-col gap-2 items-end z-10 pointer-events-none select-none text-[11px] text-nexboard-on-surface-variant/80">
                    <div class="bg-black/45 border border-white/5 px-3 py-1.5 rounded-xl shadow-lg pointer-events-auto flex items-center gap-4">
                        <div class="flex items-center gap-1">
                            <span class="px-1.5 py-0.5 rounded bg-white/10 text-white font-mono text-[10px]">Space + Drag</span>
                            <span>Pan</span>
                        </div>
                        <div class="w-px h-3 bg-white/10" />
                        <div class="flex items-center gap-1">
                            <span class="px-1.5 py-0.5 rounded bg-white/10 text-white font-mono text-[10px]">Wheel</span>
                            <span>Zoom ({{ Math.round(zoom * 100) }}%)</span>
                        </div>
                        <div class="w-px h-3 bg-white/10" />
                        <div class="flex items-center gap-1">
                            <span class="px-1.5 py-0.5 rounded bg-white/10 text-white font-mono text-[10px]">Del / Bksp</span>
                            <span>Delete</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Architects+Daughter&display=swap');
</style>
