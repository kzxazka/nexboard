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
    { id: 'select', label: 'Selection', icon: 'M6 3l12 9-5.5.5L16 21l-3 1-3.5-9.5L6 14V3z' },
    { id: 'hand', label: 'Hand (Pan)', icon: 'M9 14V4a1.5 1.5 0 0 1 3 0v7M12 11V2a1.5 1.5 0 0 1 3 0v9M15 11V3a1.5 1.5 0 0 1 3 0v8M18 11v4a5 5 0 0 1-10 0v-3.5a1.5 1.5 0 0 1 3 0V11' },
    { id: 'draw', label: 'Draw (Freehand)', icon: 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 1 1 3.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z' },
    { id: 'line', label: 'Line', icon: 'M5 19L19 5' },
    { id: 'arrow', label: 'Arrow', icon: 'M19 5H12M19 5v7M19 5L5 19' },
    { id: 'rectangle', label: 'Rectangle', icon: 'M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z' },
    { id: 'ellipse', label: 'Ellipse', icon: 'M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z' },
    { id: 'diamond', label: 'Diamond', icon: 'M12 2L2 12l10 10 10-10L12 2z' },
    { id: 'text', label: 'Text', icon: 'M4 7V4h16v3M12 4v16M9 20h6' },
    { id: 'eraser', label: 'Eraser', icon: 'M18 13l-6 6H6v-6l6-6 6 6z M12 7L6 13 M21 20H12' },
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

const zoomIn = () => {
    zoom.value = Math.min(zoom.value + 0.1, 3);
    drawScene();
};

const zoomOut = () => {
    zoom.value = Math.max(zoom.value - 0.1, 0.1);
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
    <div class="h-screen w-screen relative bg-[#121212] overflow-hidden select-none font-sans">
        
        <!-- CANVAS DRAWING AREA -->
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
            class="absolute inset-0 w-full h-full block z-0"
            :class="activeTool === 'hand' ? (isSpacePressed ? 'cursor-grabbing' : 'cursor-grab') : activeTool === 'text' ? 'cursor-text' : 'cursor-crosshair'"
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

        <!-- FLOATING UI LAYER -->
        <div class="absolute inset-0 pointer-events-none z-10 p-4 flex flex-col justify-between">
            
            <!-- TOP ROW -->
            <div class="flex items-start justify-between w-full relative">
                
                <!-- Top Left: Back & Title -->
                <div class="pointer-events-auto flex items-center gap-3 bg-[#232329]/95 border border-white/10 rounded-xl px-3 py-2 shadow-lg backdrop-blur-xl">
                    <Link :href="route('sketches.index')" class="p-1.5 hover:bg-white/10 rounded-lg text-slate-300 hover:text-nexboard-on-surface transition-colors flex items-center gap-1.5 group" title="Back to Sketches">
                        <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                        <span class="text-[11px] font-semibold tracking-wide pr-1">Back</span>
                    </Link>
                    <div class="w-px h-6 bg-white/10"></div>
                    <div class="flex flex-col pr-2">
                        <span class="text-sm font-semibold text-nexboard-on-surface tracking-wide block leading-none">{{ sketch.title }}</span>
                        <span class="text-[10px] text-slate-400 mt-1 block leading-none">{{ sketch.project?.name || 'Personal Canvas' }}</span>
                    </div>
                </div>

                <!-- Top Center: Main Tools Palette -->
                <div class="absolute left-1/2 -translate-x-1/2 pointer-events-auto flex items-center gap-1 bg-[#232329]/95 border border-white/10 rounded-xl px-2 py-1.5 shadow-lg backdrop-blur-xl">
                    <button
                        v-for="(tool, index) in tools"
                        :key="tool.id"
                        @click="activeTool = tool.id"
                        :class="[
                            'p-2 rounded-lg transition-all duration-200 group relative',
                            activeTool === tool.id ? 'bg-indigo-500/20 text-indigo-400' : 'text-slate-300 hover:bg-white/5 hover:text-nexboard-on-surface'
                        ]"
                    >
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="tool.icon" />
                        </svg>
                        
                        <!-- Tooltip -->
                        <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 bg-black/80 text-nexboard-on-surface text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                            {{ tool.label }} <span class="text-slate-400 ml-1">{{ index + 1 === 10 ? '0' : index + 1 }}</span>
                        </span>
                    </button>
                </div>

                <!-- Top Right: Export & Save -->
                <div class="pointer-events-auto flex items-center gap-2">
                    <div v-if="saving" class="bg-[#232329]/95 border border-white/10 rounded-xl px-3 py-2 flex items-center gap-2 shadow-lg backdrop-blur-xl">
                        <svg class="animate-spin h-3.5 w-3.5 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="text-xs text-indigo-400 font-medium">Saving...</span>
                    </div>
                    <div v-else-if="lastSaved" class="bg-[#232329]/95 border border-white/10 rounded-xl px-3 py-2 flex items-center shadow-lg backdrop-blur-xl">
                        <span class="text-xs text-slate-400">Saved</span>
                    </div>

                    <div class="bg-[#232329]/95 border border-white/10 rounded-xl p-1 flex items-center shadow-lg backdrop-blur-xl">
                        <button @click="exportPNG" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-300 hover:text-nexboard-on-surface hover:bg-white/5 transition-colors">
                            Export
                        </button>
                        <div class="w-px h-4 bg-white/10 mx-1"></div>
                        <button @click="saveCanvas" :disabled="saving" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-500 text-nexboard-on-surface hover:bg-indigo-600 transition-colors">
                            Save
                        </button>
                    </div>
                </div>
            </div>

            <!-- MIDDLE ROW: Properties Panel -->
            <div class="flex-1 w-full relative mt-4">
                <div v-if="activeTool !== 'hand' && activeTool !== 'select'" class="pointer-events-auto absolute left-0 top-0 w-52 bg-[#232329]/95 border border-white/10 rounded-xl p-4 shadow-xl backdrop-blur-xl flex flex-col gap-4 overflow-y-auto max-h-full custom-scrollbar">
                    
                    <!-- STROKE COLOR -->
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 block mb-2">Stroke</label>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="c in colors"
                                :key="c"
                                @click="strokeColor = c"
                                :class="strokeColor === c ? 'ring-2 ring-indigo-500 ring-offset-2 ring-offset-[#232329]' : 'hover:scale-110'"
                                :style="{ backgroundColor: c }"
                                class="w-5 h-5 rounded transition-all border border-white/15"
                            />
                        </div>
                    </div>

                    <!-- FILL COLOR -->
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 block mb-2">Background</label>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="fc in fillColors"
                                :key="fc"
                                @click="fillColor = fc"
                                :class="fillColor === fc ? 'ring-2 ring-indigo-500 ring-offset-2 ring-offset-[#232329]' : 'hover:scale-110'"
                                :style="{ backgroundColor: fc === 'none' ? 'transparent' : fc }"
                                class="w-5 h-5 rounded transition-all border border-white/15 relative flex items-center justify-center"
                            >
                                <span v-if="fc === 'none'" class="text-red-500 text-xs font-bold leading-none -rotate-45">/</span>
                            </button>
                        </div>
                    </div>

                    <!-- FILL STYLE -->
                    <div v-if="fillColor !== 'none'">
                        <label class="text-[10px] font-bold text-slate-400 block mb-2">Fill</label>
                        <div class="grid grid-cols-3 gap-1 bg-black/20 p-1 rounded-lg">
                            <button
                                v-for="style in ['hachure', 'solid', 'cross-hatch']"
                                :key="style"
                                @click="fillStyle = style"
                                :class="fillStyle === style ? 'bg-indigo-500/20 text-indigo-400 shadow-sm' : 'text-slate-400 hover:text-nexboard-on-surface hover:bg-white/5'"
                                class="text-[10px] py-1.5 rounded-md capitalize transition-all"
                            >
                                {{ style === 'hachure' ? 'Sketch' : style === 'cross-hatch' ? 'Hatch' : 'Solid' }}
                            </button>
                        </div>
                    </div>

                    <!-- STROKE WIDTH -->
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 block mb-2">Stroke width</label>
                        <div class="grid grid-cols-3 gap-1 bg-black/20 p-1 rounded-lg text-center">
                            <button
                                v-for="w in [1, 2, 5]"
                                :key="w"
                                @click="strokeWidth = w"
                                :class="strokeWidth === w ? 'bg-indigo-500/20 text-indigo-400 shadow-sm' : 'text-slate-400 hover:text-nexboard-on-surface hover:bg-white/5'"
                                class="text-[10px] py-1.5 rounded-md transition-all flex justify-center items-center"
                            >
                                <div class="bg-current rounded-full" :style="{ width: w*2+'px', height: w*2+'px' }"></div>
                            </button>
                        </div>
                    </div>

                    <!-- STROKE STYLE -->
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 block mb-2">Stroke style</label>
                        <div class="grid grid-cols-3 gap-1 bg-black/20 p-1 rounded-lg text-center">
                            <button
                                v-for="style in ['solid', 'dashed', 'dotted']"
                                :key="style"
                                @click="strokeStyle = style"
                                :class="strokeStyle === style ? 'bg-indigo-500/20 text-indigo-400 shadow-sm' : 'text-slate-400 hover:text-nexboard-on-surface hover:bg-white/5'"
                                class="text-[10px] py-1.5 rounded-md transition-all flex justify-center items-center"
                            >
                                <div v-if="style === 'solid'" class="w-4 h-0.5 bg-current rounded-full"></div>
                                <div v-else-if="style === 'dashed'" class="w-4 h-0.5 border-t-2 border-dashed border-current"></div>
                                <div v-else class="w-4 h-0.5 border-t-2 border-dotted border-current"></div>
                            </button>
                        </div>
                    </div>

                    <!-- SLOPIUNESS -->
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 block mb-2">Sloppiness</label>
                        <div class="grid grid-cols-3 gap-1 bg-black/20 p-1 rounded-lg text-center">
                            <button
                                v-for="(r, i) in [0, 1.2, 2.5]"
                                :key="r"
                                @click="roughness = r"
                                :class="roughness === r ? 'bg-indigo-500/20 text-indigo-400 shadow-sm' : 'text-slate-400 hover:text-nexboard-on-surface hover:bg-white/5'"
                                class="text-[10px] py-1.5 rounded-md transition-all flex justify-center items-center"
                                :title="r === 0 ? 'Architect' : r === 1.2 ? 'Artist' : 'Cartoon'"
                            >
                                <svg v-if="i === 0" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" /></svg>
                                <svg v-else-if="i === 1" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- ACTIONS -->
                    <div class="mt-2 space-y-1">
                        <button
                            @click="showGrid = !showGrid"
                            :class="showGrid ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-300 hover:bg-white/5'"
                            class="w-full py-1.5 rounded-lg text-[10px] font-semibold flex items-center gap-2 px-2 transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                            </svg>
                            Show Grid
                        </button>
                        <button @click="clearCanvas" class="w-full py-1.5 rounded-lg text-[10px] font-semibold flex items-center gap-2 px-2 text-red-400 hover:bg-red-500/10 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            Clear Canvas
                        </button>
                    </div>
                </div>
            </div>

            <!-- BOTTOM ROW -->
            <div class="flex items-end justify-between w-full mt-auto">
                
                <!-- Bottom Left: Zoom and Undo/Redo -->
                <div class="pointer-events-auto flex items-center gap-3">
                    <div class="flex items-center bg-[#232329]/95 border border-white/10 rounded-xl p-1 shadow-lg backdrop-blur-xl">
                        <button @click="zoomOut" class="p-1 hover:bg-white/10 rounded text-slate-300 hover:text-nexboard-on-surface transition-colors" title="Zoom Out">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" /></svg>
                        </button>
                        <span class="text-xs text-nexboard-on-surface font-mono w-12 text-center" @click="zoom = 1">{{ Math.round(zoom * 100) }}%</span>
                        <button @click="zoomIn" class="p-1 hover:bg-white/10 rounded text-slate-300 hover:text-nexboard-on-surface transition-colors" title="Zoom In">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-1 bg-[#232329]/95 border border-white/10 rounded-xl p-1 shadow-lg backdrop-blur-xl">
                        <button @click="handleUndo" class="p-1.5 hover:bg-white/10 rounded text-slate-300 hover:text-nexboard-on-surface transition-colors" title="Undo (Ctrl+Z)">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                        </button>
                        <button @click="handleRedo" class="p-1.5 hover:bg-white/10 rounded text-slate-300 hover:text-nexboard-on-surface transition-colors" title="Redo (Ctrl+Y)">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Bottom Right: Helpers -->
                <div class="pointer-events-auto bg-[#232329]/95 border border-white/10 rounded-xl p-2 shadow-lg backdrop-blur-xl flex items-center gap-4 text-[10px] text-slate-400">
                    <div class="flex items-center gap-1.5">
                        <span class="px-1.5 py-0.5 rounded bg-white/10 text-slate-300 font-mono">Space</span>
                        <span>+ Drag to Pan</span>
                    </div>
                    <div class="w-px h-3 bg-white/10"></div>
                    <div class="flex items-center gap-1.5">
                        <span class="px-1.5 py-0.5 rounded bg-white/10 text-slate-300 font-mono">Wheel</span>
                        <span>to Zoom</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Architects+Daughter&display=swap');

/* Custom scrollbar for Excalidraw options panel */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>
