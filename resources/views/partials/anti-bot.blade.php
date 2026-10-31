{{-- Freno a robots: campo trampa invisible + hora en que se abrió el formulario (lo revisa App\Http\Middleware\AntiBot) --}}
<div aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden">
    <label for="web_empresa">No llene este campo</label>
    <input type="text" name="web_empresa" id="web_empresa" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="_lp_t" value="{{ encrypt(time()) }}">
