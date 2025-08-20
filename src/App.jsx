import React from 'react';
import { Wave } from 'react-bits';

function App() {
  return (
    <div className="app">
      <div className="wave-container">
        <Wave
          fill="#4F46E5"
          paused={false}
          options={{
            height: 100,
            amplitude: 50,
            speed: 0.15,
            points: 3
          }}
        />
      </div>
      <div className="content">
        {/* This will be populated by your PHP content */}
      </div>
    </div>
  );
}

export default App;
