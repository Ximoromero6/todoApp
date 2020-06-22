import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';

@Component({
  selector: 'app-registro',
  templateUrl: './registro.component.html',
  styleUrls: ['./registro.component.scss']
})
export class RegistroComponent implements OnInit {

  formularioRegistro: FormGroup
  constructor(
    private formBuilder: FormBuilder
  ) { }

  ngOnInit(): void {
    this.formularioRegistro = this.formBuilder.group({
      nombre: ['fdfdf'],
      usuario: ['fdfdf', Validators.compose([Validators.minLength(4), Validators.required])],
      email: ['fdfdf@sss', Validators.compose([Validators.email, Validators.required])],
      clave: ['fdfdfss', Validators.compose([Validators.minLength(6), Validators.required])]
    });
  }

  validarDatosRegistro() {
    if (this.formularioRegistro.invalid) {
      this.formularioRegistro.get("nombre").markAsDirty();
      this.formularioRegistro.get("usuario").markAsDirty();
      this.formularioRegistro.get("email").markAsDirty();
      this.formularioRegistro.get("clave").markAsDirty();
      return;
    }

    console.log(JSON.stringify(this.formularioRegistro.value));
  }

}
