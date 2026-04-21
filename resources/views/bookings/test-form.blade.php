@extends('layouts.app')

@section('title', 'Test Form')

@section('content')
<div class="container py-5">
    <h2>Test Dynamic Form</h2>
    
    <select id="roomCount" onchange="testRender()">
        <option value="">Chọn số phòng</option>
        <option value="1">1 phòng</option>
        <option value="2">2 phòng</option>
    </select>
    
    <div id="roomsContainer"></div>
</div>

<script>
function testRender() {
    console.log('=== TEST RENDER CALLED ===');
    const count = document.getElementById('roomCount').value;
    const container = document.getElementById('roomsContainer');
    
    console.log('Room count:', count);
    
    if (!count) {
        container.innerHTML = '';
        return;
    }
    
    let html = '';
    for (let i = 0; i < count; i++) {
        html += `
            <div style="border: 1px solid red; margin: 10px; padding: 10px;">
                <h3>Phòng ${i + 1}</h3>
                <input type="number" value="1" onchange="testGuest(${i})">
                <div id="guests_${i}"></div>
            </div>
        `;
    }
    
    container.innerHTML = html;
    
    // Auto render guests
    for (let i = 0; i < count; i++) {
        testGuest(i);
    }
}

function testGuest(roomIndex) {
    console.log('=== TEST GUEST CALLED FOR ROOM', roomIndex, '===');
    const container = document.getElementById(`guests_${roomIndex}`);
    if (!container) {
        console.log('Container not found for room', roomIndex);
        return;
    }
    
    container.innerHTML = `
        <div style="border: 1px solid blue; padding: 5px;">
            <h4>Guest 1 - Room ${roomIndex + 1}</h4>
            <input name="rooms[${roomIndex}][guests][0][name]" placeholder="Name">
            <input name="rooms[${roomIndex}][guests][0][cccd]" placeholder="CCCD">
        </div>
    `;
    
    console.log('Guest rendered for room', roomIndex);
}

console.log('Test script loaded');
</script>
@endsection
