<?php
include 'config.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM rooms");

echo "<div class='room-grid'>";
while($row = $result->fetch_assoc()) {
  echo "
    <div class='room-card'>
      <h3>{$row['name']}</h3>
      <p><strong>Status:</strong> {$row['status']}</p>
      <p><strong>Price:</strong> \${$row['price']}/night</p>
      <p><strong>Size:</strong> {$row['size']} | Guests: {$row['guests']}</p>
      <p>{$row['description']}</p>
      <p><strong>Amenities:</strong> {$row['amenities']}</p>
      <a href='book.php?id={$row['id']}' class='btn'>Book Now</a>
    </div>
  ";
}
echo "</div>";

$conn->close();
?>
