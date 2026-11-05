<div class="modal" id="session-modal" hidden data-testid="session-modal">
        <div class="modal-backdrop" data-close-modal></div>
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modal-title">
          <button class="icon-btn modal-close" data-close-modal data-testid="session-modal-close"><?= icon('x', 20) ?></button>
          <div class="modal-img" id="modal-img"></div>
          <div class="modal-body">
            <span class="tag" id="modal-cat" data-testid="session-modal-category"></span>
            <h2 id="modal-title" data-testid="session-modal-title"></h2>
            <ul class="meta-list">
              <li><?= icon('calendar', 16) ?><span id="modal-date" data-testid="session-modal-date"></span></li>
              <li><?= icon('user', 16) ?><span id="modal-coach" data-testid="session-modal-coach"></span></li>
              <li><?= icon('pin', 16) ?><span id="modal-room"></span></li>
              <li><?= icon('signal', 16) ?><span id="modal-level"></span></li>
            </ul>
            <div class="places">
              <div class="places-top"><span>Places</span><strong id="modal-places" data-testid="session-modal-places"></strong></div>
              <div class="bar"><i id="modal-bar"></i></div>
            </div>
            <button class="btn btn-primary btn-block" id="modal-action" data-testid="session-modal-action-button"></button>
          </div>
        </div>
      </div>
