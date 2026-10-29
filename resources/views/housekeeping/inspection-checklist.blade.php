{{-- Supervisor inspection modal. Opened from a room card; posts to housekeeping.rooms.inspect for that room. --}}
<div x-show="modal === 'inspect'" x-cloak class="hk-modal-backdrop" @click.self="close()" @keydown.escape.window="close()" role="dialog" aria-modal="true" aria-labelledby="inspect-title">
    <div class="hk-modal" x-transition.opacity.duration.150ms>
        <div class="hk-modal__head">
            <div>
                <h2 id="inspect-title">Inspect room <span x-text="roomNumber"></span></h2>
                <p>Tick each item you have verified, then pass or return the room.</p>
            </div>
            <button type="button" class="hk-close" @click="close()" aria-label="Close">&times;</button>
        </div>
        <form method="POST" :action="actionFor('inspect')" class="hk-form">
            @csrf
            <label class="hk-check"><input type="checkbox" name="bedding" value="1"> Bedding and linens replaced</label>
            <label class="hk-check"><input type="checkbox" name="bathroom" value="1"> Bathroom disinfected and restocked</label>
            <label class="hk-check"><input type="checkbox" name="trash_floors" value="1"> Trash emptied and floors vacuumed</label>
            <label class="hk-check"><input type="checkbox" name="amenities" value="1"> Minibar and amenities verified</label>
            <div>
                <label class="field-label" for="inspect-notes">Note (required if returning the room)</label>
                <textarea id="inspect-notes" name="notes" rows="2" class="input" placeholder="e.g. Bathroom mirror needs re-wiping"></textarea>
            </div>
            <div class="hk-actions">
                <button type="submit" name="decision" value="fail" class="hk-btn hk-btn--danger">Return to housekeeping</button>
                <button type="submit" name="decision" value="pass" class="hk-btn hk-btn--primary">Pass inspection</button>
            </div>
        </form>
    </div>
</div>
