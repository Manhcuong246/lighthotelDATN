<!DOCTYPE html>
<html>
<head>
    <title>Test Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Test Dynamic Form</h2>
        
        <select id="roomCount" onchange="renderRooms()" class="form-select mb-3">
            <option value="">Chọn số phòng</option>
            <option value="1">1 phòng</option>
            <option value="2">2 phòng</option>
        </select>
        
        <div id="roomsContainer"></div>
    </div>

    <script>
    function renderRooms() {
        console.log('renderRooms called!');
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
                <div style="border: 2px solid red; margin: 10px; padding: 10px;">
                    <h3>Phòng ${i + 1}</h3>
                    <input type="number" value="1" onchange="renderGuests(${i})" class="form-control mb-2">
                    <div id="guests_${i}"></div>
                </div>
            `;
        }
        
        container.innerHTML = html;
        
        // Auto render guests
        for (let i = 0; i < count; i++) {
            renderGuests(i);
        }
    }
    
    function renderGuests(roomIndex) {
        console.log('renderGuests called for room:', roomIndex);
        const container = document.getElementById(`guests_${roomIndex}`);
        if (!container) return;
        
        container.innerHTML = `
            <div style="border: 2px solid blue; padding: 10px;">
                <h4>Guest 1 - Room ${roomIndex + 1}</h4>
                <input name="rooms[${roomIndex}][guests][0][name]" placeholder="Name" class="form-control mb-2">
                <input name="rooms[${roomIndex}][guests][0][cccd]" placeholder="CCCD" class="form-control">
            </div>
        `;
    }
    
    console.log('Test script loaded!');
    </script>
</body>
</html>
