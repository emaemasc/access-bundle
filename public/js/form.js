document.addEventListener('DOMContentLoaded', function() {

    const presets = document.querySelectorAll('.emaemasc-access-preset')
    const headers = document.querySelectorAll('.emaemasc-access-group-header')
    const toggles = document.querySelectorAll('.emaemasc-input')

    function checkPresets() {
        const inputs = document.querySelectorAll('.emaemasc-input')
        const enabled = []
        inputs.forEach(input => {
            if (input.checked) enabled.push(input.dataset.itemId)
        })
        presets.forEach(p => {
            const items = JSON.parse(p.dataset.items)
            p.classList.remove('active')
            if (items.length === enabled.length && items.filter(i => enabled.includes(i)).length === items.length) {
                p.classList.add('active')
            }
        })
    }

    checkPresets()

    presets.forEach(preset => {
        preset.onclick = function () {
            const items = JSON.parse(this.dataset.items)
            const inputs = document.querySelectorAll('.emaemasc-input')
            const isActive = preset.classList.contains('active')
            if (isActive) preset.classList.remove('active')
            inputs.forEach(input => {
                input.value = 0
                input.checked = false
                if (items.includes(input.dataset.itemId)) {
                    input.value = !isActive
                    input.checked = !isActive
                }
            })
            checkPresets()
        }
    })

    headers.forEach(header => {
        header.onclick = function () {
            const isActive = this.classList.contains('active')
            const inputs = this.parentElement.querySelectorAll('.emaemasc-input')
            this.classList.toggle('active')
            inputs.forEach(input => {
                input.value = !isActive
                input.checked = !isActive
            })
            checkPresets()
        }
    })


    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            checkPresets()
        })
    })
})
